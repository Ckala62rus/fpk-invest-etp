<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cms_pages', function (Blueprint $table) {
            $table->id()->comment('Идентификатор страницы');
            $table->string('slug')->unique()->comment('URL-slug страницы');
            $table->string('title')->comment('Заголовок страницы');
            $table->string('meta_title')->nullable()->comment('SEO title');
            $table->string('meta_description', 500)->nullable()->comment('SEO description');
            $table->boolean('is_published')->default(false)->comment('Опубликована ли страница');
            $table->unsignedInteger('sort_order')->default(0)->comment('Порядок в меню');
            $table->timestamps();
            $table->softDeletes()->comment('Мягкое удаление');
        });

        Schema::create('cms_page_revisions', function (Blueprint $table) {
            $table->id()->comment('Идентификатор ревизии');
            $table->foreignId('page_id')->constrained('cms_pages')->cascadeOnDelete()->comment('Страница CMS');
            $table->longText('content_html')->comment('HTML-содержимое страницы');
            $table->foreignId('revised_by')->constrained('users')->comment('Кто отредактировал');
            $table->timestamp('created_at')->nullable()->comment('Дата ревизии');
        });

        DB::statement("COMMENT ON TABLE cms_pages IS 'Информационные страницы CMS (О площадке, Правила и т.д.)'");
        DB::statement("COMMENT ON TABLE cms_page_revisions IS 'История версий страниц CMS'");
    }

    public function down(): void
    {
        Schema::dropIfExists('cms_page_revisions');
        Schema::dropIfExists('cms_pages');
    }
};
