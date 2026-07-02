<?php

use App\Support\Migration\AddsPostgresEnumColumn;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    use AddsPostgresEnumColumn;

    public function up(): void
    {
        Schema::create('user_profiles', function (Blueprint $table) {
            $table->id()->comment('Идентификатор профиля');
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete()->comment('Пользователь');
            $table->string('name', 500)->comment('Наименование организации или ФИО');
            $table->string('phone', 20)->comment('Контактный телефон');
            $table->string('director_name')->comment('ФИО руководителя');
            $table->date('director_birth_date')->nullable()->comment('Дата рождения руководителя');
            $table->text('contact_persons')->comment('Контактные лица');
            $table->timestamp('pd_consent_at')->comment('Дата согласия на обработку персональных данных');
            $table->timestamps();
        });

        $this->addEnumColumn(
            table: 'user_profiles',
            column: 'entity_type',
            enumType: 'entity_type',
            default: 'legal',
            comment: 'Тип субъекта: legal — юрлицо, individual — физлицо',
        );

        Schema::create('user_emails', function (Blueprint $table) {
            $table->id()->comment('Идентификатор дополнительного email');
            $table->foreignId('user_id')->constrained()->cascadeOnDelete()->comment('Пользователь');
            $table->string('email')->comment('Дополнительный email');
            $table->timestamp('created_at')->nullable()->comment('Дата добавления');
        });

        Schema::create('user_documents', function (Blueprint $table) {
            $table->id()->comment('Идентификатор документа');
            $table->foreignId('user_id')->constrained()->cascadeOnDelete()->comment('Пользователь');
            $table->string('file_path', 500)->comment('Путь к файлу');
            $table->string('file_name')->comment('Имя файла');
            $table->string('mime_type', 100)->nullable()->comment('MIME-тип файла');
            $table->unsignedBigInteger('size')->nullable()->comment('Размер файла в байтах');
            $table->date('valid_until')->comment('Срок действия документа (1 год с загрузки)');
            $table->timestamp('uploaded_at')->nullable()->comment('Дата загрузки');
            $table->timestamps();
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->id()->comment('Идентификатор токена');
            $table->foreignId('user_id')->constrained()->cascadeOnDelete()->comment('Пользователь');
            $table->string('token')->unique()->comment('Токен восстановления пароля');
            $table->timestamp('expires_at')->comment('Срок действия ссылки');
            $table->timestamp('used_at')->nullable()->comment('Дата использования токена');
            $table->timestamp('created_at')->nullable()->comment('Дата создания');
        });

        Schema::create('password_reset_admin_requests', function (Blueprint $table) {
            $table->id()->comment('Идентификатор запроса');
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete()->comment('Пользователь (если найден)');
            $table->string('inn', 12)->nullable()->comment('ИНН из запроса');
            $table->text('message')->nullable()->comment('Текст обращения');
            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete()->comment('Администратор, обработавший запрос');
            $table->timestamps();
        });

        $this->addEnumColumn(
            table: 'password_reset_admin_requests',
            column: 'status',
            enumType: 'password_reset_admin_status',
            default: 'pending',
            comment: 'Статус запроса восстановления через администратора',
        );

        DB::statement("COMMENT ON TABLE user_profiles IS 'Профили пользователей (данные регистрации)'");
        DB::statement("COMMENT ON TABLE user_emails IS 'Дополнительные email пользователей'");
        DB::statement("COMMENT ON TABLE user_documents IS 'Учредительные и регистрационные документы'");
        DB::statement("COMMENT ON TABLE password_reset_tokens IS 'Токены восстановления пароля'");
        DB::statement("COMMENT ON TABLE password_reset_admin_requests IS 'Запросы восстановления доступа через главного администратора'");
    }

    public function down(): void
    {
        Schema::dropIfExists('password_reset_admin_requests');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('user_documents');
        Schema::dropIfExists('user_emails');
        Schema::dropIfExists('user_profiles');
    }
};
