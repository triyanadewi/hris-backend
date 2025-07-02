<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {

        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email');
            $table->string('head_office_phone');
            $table->string('head_office_phone_backup')->nullable();
            $table->string('head_office_address');
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('name')->nullable();
            $table->string('email')->unique();
            $table->string('password');
            $table->enum('role', ['admin', 'employee']);
            $table->boolean('isProfileCompany')->default(false);
            $table->timestamps();
        });

        Schema::create('admin', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); 
            $table->string('phone_number')->nullable();
            $table->string('profile_photo')->nullable();
            $table->timestamps();
        });

        Schema::create('branches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('branch_address');
            $table->string('branch_phone');
            $table->string('branch_phone_backup')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('divisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained('branches')->onDelete('cascade');
            $table->string('name');
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('positions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('division_id')->constrained('divisions')->onDelete('cascade');
            $table->string('name');
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('Company_id')->constrained('companies')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('Branch_id')->constrained('branches')->onDelete('cascade');
            $table->foreignId('Division_id')->constrained('divisions')->onDelete('cascade');
            $table->foreignId('Position_id')->constrained('positions')->onDelete('cascade');
            $table->string('EmployeeID')->unique();
            $table->string('FirstName');
            $table->string('LastName');
            $table->string('Gender');
            $table->text('Address');
            $table->string('PhoneNumber');
            $table->string('Status');
            $table->string('NIK');
            $table->string('LastEducation');
            $table->string('PlaceOfBirth');
            $table->string('BirthDate');
            $table->string('ContractType');
            $table->string('Bank');
            $table->string('BankAccountNumber');
            $table->string('BankAccountHolderName');
            $table->string('photo');
            $table->text('Notes')->nullable();
            $table->timestamps();
        });

        Schema::create('achievements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->onDelete('cascade');
            $table->string('file_path')->nullable();
            $table->string('original_filename')->nullable()->after('file_path');
            $table->timestamps();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
            $table->timestamps();
        });

        Schema::create('letter_formats', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('content');
            $table->enum('type', ['published', 'archived', 'draft']);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('letters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('letter_format_id')->constrained('letter_formats')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->date('resignation_date')->nullable();
            $table->text('reason_resign')->nullable();
            $table->text('additional_notes')->nullable();
            $table->string('current_division')->nullable();
            $table->string('requested_division')->nullable();
            $table->text('reason_transfer')->nullable();
            $table->integer('current_salary')->nullable();
            $table->integer('requested_salary')->nullable(); // perbaiki huruf kecil semua
            $table->text('reason_salary')->nullable();
            $table->date('leave_start')->nullable();
            $table->date('return_to_work')->nullable();
            $table->text('reason_for_leave')->nullable();
            $table->boolean('is_sent')->default(false);
            $table->boolean('is_approval')->default(false);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('check_clock_settings', function (Blueprint $table) {
            $table->id();
            
            // Lokasi presensi milik perusahaan & cabang (branch_id nullable = head office)
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->foreignId('branch_id')->nullable()->constrained('branches')->onDelete('cascade');
            
            // Nama lokasi dan koordinat
            $table->string('location_name');
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->integer('radius')->nullable(); // radius dalam meter
            
            $table->timestamps();
            $table->softDeletes();
            
            // Prevent duplicate setting per branch
            $table->unique(['company_id', 'branch_id'], 'uniq_ck_setting_location');
        });

        Schema::create('check_clock_setting_times', function (Blueprint $table) {
            $table->id();
            
            // Setting waktu berdasarkan lokasi tertentu
            $table->foreignId('ck_settings_id')->constrained('check_clock_settings')->onDelete('cascade');
            $table->enum('day', ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday']);
            
            // Clock in
            $table->time('clock_in_start');         // contoh: 07:00
            $table->time('clock_in_end');           // contoh: 09:00
            $table->time('clock_in_on_time_limit'); // contoh: 08:00 (setelah ini status "Late")
            
            // Clock out
            $table->time('clock_out_start');        // contoh: 17:00
            $table->time('clock_out_end');          // contoh: 19:00
            
            $table->boolean('work_day')->default(true); // apakah hari kerja?

            $table->timestamps();
            $table->softDeletes();

            // Prevent duplicate setting untuk hari tertentu
            $table->unique(['ck_settings_id', 'day'], 'uniq_setting_day');
        });

        Schema::create('check_clocks', function (Blueprint $table) {
            $table->id();

            // Presensi oleh employee tertentu
            $table->foreignId('employee_id')->constrained('employees')->onDelete('cascade');
            
            // Setting yang digunakan (lokasi + aturan waktu)
            $table->foreignId('ck_settings_id')->nullable()->constrained('check_clock_settings')->onDelete('set null');
            $table->foreignId('branch_id')->nullable()->constrained('branches')->onDelete('set null');
            
            // Jenis presensi
            $table->enum('check_clock_type', ['check-in', 'check-out', 'annual-leave', 'sick-leave', 'absent']);
            
            // Waktu presensi
            $table->date('check_clock_date');
            $table->time('check_clock_time');
            $table->time('check_out_time')->nullable();
            
            // Jika jenisnya cuti/libur
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            
            // Status presensi
            $table->enum('status', ['On Time', 'Late', 'Absent', 'Annual Leave', 'Sick Leave', 'Waiting Approval', '-'])->nullable();
            $table->boolean('approved')->nullable(); // manual approve jika diperlukan
            
            // Lokasi aktual presensi
            $table->string('location')->nullable();  // lokasi dari front-end / nama lokasi
            $table->string('address')->nullable();   // alamat GPS reverse-geocoding
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            
            // Foto presensi (opsional)
            $table->string('photo')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('salaries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->integer('type');
            $table->float('rate');
            $table->date('effective_date');
            $table->string('status');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('personal_access_tokens', function (Blueprint $table) {
            $table->id();
            $table->morphs('tokenable');
            $table->string('name');
            $table->string('token', 64)->unique();
            $table->text('abilities')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });

        // Tabel Package Plans
        Schema::create('packages', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->integer('price');
            $table->string('description');
            $table->timestamps();
        });

        Schema::create('package_benefits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('package_id')->constrained('packages')->onDelete('cascade');
            $table->unsignedBigInteger('max_branches');
            $table->unsignedBigInteger('max_employees');
            $table->unsignedInteger('access_duration_days');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Tabel Checkout
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('package_benefits_id')->constrained('package_benefits')->onDelete('cascade');
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');

            // Add the missing fields that are being sent from frontend
            $table->string('company_name');
            $table->string('email');
            $table->string('phone_number');

            $table->integer('add_branch')->nullable()->default(0);
            $table->integer('add_employees')->nullable()->default(0);
            $table->unsignedBigInteger('duration_days');
            $table->bigInteger('subtotal');
            $table->bigInteger('tax');
            $table->bigInteger('total');
            $table->string('status')->default('pending'); // pending, paid, failed, etc
            $table->string('payment_method')->nullable();
            $table->string('payment_reference')->nullable(); // dari Xendit misal
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });

        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->foreignId('package_id')->constrained();
            $table->unsignedBigInteger('extra_branch')->default(0); // dari orders
            $table->unsignedBigInteger('extra_employee')->default(0);
            $table->date('starts_at');
            $table->date('ends_at');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::table('orders', function (Blueprint $table) {
            
            $table->dropForeign(['package_benefits_id']);
            // $table->renameColumn('package_benefits_id', 'package_benefits_id');
            $table->foreign('package_benefits_id')->references('id')->on('package_benefits')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('checkouts');
        Schema::dropIfExists('package_plans');
        Schema::dropIfExists('personal_access_tokens');
        Schema::dropIfExists('salaries');
        Schema::dropIfExists('check_clocks');
        Schema::dropIfExists('check_clock_setting_times');
        Schema::dropIfExists('check_clock_settings');
        Schema::dropIfExists('letters');
        Schema::dropIfExists('letter_formats');
        Schema::dropIfExists('achievements');
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('admin');
        Schema::dropIfExists('employees');
        Schema::dropIfExists('positions');
        Schema::dropIfExists('divisions');
        Schema::dropIfExists('branches'); 
        Schema::dropIfExists('users');
        Schema::dropIfExists('companies');
        Schema::dropIfExists('personal_access_tokens');
        Schema::dropIfExists('employee_achievements');
        Schema::dropIfExists('packages');
        Schema::dropIfExists('package_benefits');
        Schema::dropIfExists('orders');
        Schema::dropIfExists('subscriptions');
        Schema::dropIfExists('checkouts');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('orders');
    }
};
