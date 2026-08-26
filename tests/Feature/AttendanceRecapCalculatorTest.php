<?php

namespace Tests\Feature;

use App\Models\AttendanceRecap;
use App\Models\Employee;
use App\Models\Holiday;
use App\Models\WorkSchedule;
use App\Services\AttendanceRecapCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Syofyanzuhad\FilamentZktecoAdms\Models\AttendanceLog;
use Syofyanzuhad\FilamentZktecoAdms\Models\Device;
use Tests\TestCase;

class AttendanceRecapCalculatorTest extends TestCase
{
    use RefreshDatabase;

    protected AttendanceRecapCalculator $calculator;

    protected Device $device;

    protected function setUp(): void
    {
        parent::setUp();

        $this->calculator = app(AttendanceRecapCalculator::class);

        $this->device = Device::create(['serial_number' => 'TESTDEV001']);

        WorkSchedule::create([
            'time_in' => '08:00:00',
            'time_out' => '16:00:00',
            'late_tolerance_minutes' => 0,
            'early_leave_tolerance_minutes' => 0,
        ]);
    }

    protected function makeEmployee(array $attributes = []): Employee
    {
        return Employee::create(array_merge([
            'name' => 'Test Pegawai',
            'employee_code' => 'EMP'.random_int(1000, 9999),
            'zkteco_pin' => (string) random_int(100, 999),
            'is_admin' => false,
        ], $attributes));
    }

    protected function punch(Employee $employee, string $dateTime): void
    {
        AttendanceLog::create([
            'device_id' => $this->device->id,
            'pin' => $employee->zkteco_pin,
            'punched_at' => $dateTime,
            'status' => 0,
            'verify_type' => 1,
        ]);
    }

    public function test_hadir_tepat_waktu_tidak_telat(): void
    {
        $employee = $this->makeEmployee();
        $date = Carbon::parse('2026-08-24'); // Senin

        $this->punch($employee, '2026-08-24 07:55:00');
        $this->punch($employee, '2026-08-24 16:05:00');

        $this->calculator->recomputeForEmployeeAndDate($employee, $date);

        $recap = AttendanceRecap::where('employee_id', $employee->id)->first();

        $this->assertNotNull($recap);
        $this->assertSame(0, $recap->late_minutes);
        $this->assertSame(0, $recap->early_leave_minutes);
    }

    public function test_telat_tanpa_toleransi(): void
    {
        $employee = $this->makeEmployee();
        $date = Carbon::parse('2026-08-24');

        $this->punch($employee, '2026-08-24 08:15:00');
        $this->punch($employee, '2026-08-24 16:00:00');

        $this->calculator->recomputeForEmployeeAndDate($employee, $date);

        $recap = AttendanceRecap::where('employee_id', $employee->id)->first();

        $this->assertSame(15, $recap->late_minutes);
    }

    public function test_telat_dalam_batas_toleransi_tidak_dihitung_telat(): void
    {
        $employee = $this->makeEmployee();
        WorkSchedule::query()->update(['late_tolerance_minutes' => 15]);
        $date = Carbon::parse('2026-08-24');

        $this->punch($employee, '2026-08-24 08:10:00');
        $this->punch($employee, '2026-08-24 16:00:00');

        $this->calculator->recomputeForEmployeeAndDate($employee, $date);

        $recap = AttendanceRecap::where('employee_id', $employee->id)->first();

        $this->assertSame(0, $recap->late_minutes);
    }

    public function test_pulang_cepat_valid_saat_scan_sore_lebih_awal(): void
    {
        $employee = $this->makeEmployee();
        $date = Carbon::parse('2026-08-24');

        $this->punch($employee, '2026-08-24 08:00:00');
        $this->punch($employee, '2026-08-24 13:00:00');

        $this->calculator->recomputeForEmployeeAndDate($employee, $date);

        $recap = AttendanceRecap::where('employee_id', $employee->id)->first();

        $this->assertSame(180, $recap->early_leave_minutes);
        $this->assertNotNull($recap->check_out);
    }

    public function test_scan_ulang_pagi_tidak_dianggap_pulang_cepat_besar(): void
    {
        $employee = $this->makeEmployee();
        $date = Carbon::parse('2026-08-24');

        // Dua kali scan pagi (mis. scan ulang), tidak ada scan pulang sungguhan.
        $this->punch($employee, '2026-08-24 08:00:00');
        $this->punch($employee, '2026-08-24 10:00:00');

        $this->calculator->recomputeForEmployeeAndDate($employee, $date);

        $recap = AttendanceRecap::where('employee_id', $employee->id)->first();

        // check_out dianggap tidak valid (masih pagi, < 4 jam dari check-in) -> null,
        // dan early_leave_minutes dibatasi flat 60 menit, BUKAN ~6 jam (360 menit).
        $this->assertNull($recap->check_out);
        $this->assertSame(60, $recap->early_leave_minutes);
        $this->assertLessThan(360, $recap->early_leave_minutes);
    }

    public function test_single_scan_tidak_ada_checkout(): void
    {
        $employee = $this->makeEmployee();
        $date = Carbon::parse('2026-08-24');

        $this->punch($employee, '2026-08-24 08:00:00');

        $this->calculator->recomputeForEmployeeAndDate($employee, $date);

        $recap = AttendanceRecap::where('employee_id', $employee->id)->first();

        $this->assertNull($recap->check_out);
        $this->assertSame(60, $recap->early_leave_minutes);
    }

    public function test_hari_sabtu_minggu_tidak_direkap(): void
    {
        $employee = $this->makeEmployee();
        $sabtu = Carbon::parse('2026-08-22'); // Sabtu

        $this->punch($employee, '2026-08-22 08:00:00');

        $this->calculator->recomputeForEmployeeAndDate($employee, $sabtu);

        $this->assertDatabaseCount('attendance_recaps', 0);
    }

    public function test_hari_libur_tidak_direkap(): void
    {
        $employee = $this->makeEmployee();
        $tanggalLibur = Carbon::parse('2026-08-17'); // Senin, tapi libur

        Holiday::create(['date' => '2026-08-17', 'name' => 'Hari Kemerdekaan RI']);

        $this->punch($employee, '2026-08-17 08:00:00');

        $this->calculator->recomputeForEmployeeAndDate($employee, $tanggalLibur);

        $this->assertDatabaseCount('attendance_recaps', 0);
    }

    public function test_pegawai_admin_tidak_direkap(): void
    {
        $employee = $this->makeEmployee(['is_admin' => true]);
        $date = Carbon::parse('2026-08-24');

        $this->punch($employee, '2026-08-24 08:00:00');
        $this->punch($employee, '2026-08-24 16:00:00');

        $this->calculator->recomputeForEmployeeAndDate($employee, $date);

        $this->assertDatabaseCount('attendance_recaps', 0);
    }
}
