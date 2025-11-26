<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Criar a Tabela Mestre (Verifica se já existe)
        if (!Schema::hasTable('global_links')) {
            Schema::create('global_links', function (Blueprint $table) {
                $table->id();
                $table->string('link', 500);
                $table->unsignedBigInteger('ID_Vendedor');

                $table->string('status_link', 20)->default('PENDENTE');
                $table->dateTime('data_ultima_verificacao')->nullable();

                $table->timestamp('created_at')->useCurrent();
                $table->timestamp('updated_at')->useCurrent()->nullable();

                $table->unique(['link', 'ID_Vendedor'], 'idx_global_unico');
                $table->foreign('ID_Vendedor')->references('ID_Vendedor')->on('Vendedores')->onDelete('cascade');
            });
        }

        // 2. Migrar dados (Apenas se a tabela estiver vazia)
        if (DB::table('global_links')->count() == 0) {
            // Verifica se as colunas origem ainda existem antes de tentar ler
            if (Schema::hasColumn('links_externos', 'link') && Schema::hasColumn('links_externos', 'ID_Vendedor')) {
                DB::statement("
                    INSERT IGNORE INTO global_links (link, ID_Vendedor, status_link, data_ultima_verificacao)
                    SELECT DISTINCT link, ID_Vendedor, status_link, data_ultima_verificacao
                    FROM links_externos
                ");
            }
        }

        // 3. Adicionar coluna FK na tabela antiga
        if (!Schema::hasColumn('links_externos', 'global_link_id')) {
            Schema::table('links_externos', function (Blueprint $table) {
                $table->unsignedBigInteger('global_link_id')->nullable()->after('id');
                $table->foreign('global_link_id')->references('id')->on('global_links')->onDelete('cascade');
            });

            if (Schema::hasColumn('links_externos', 'link')) {
                DB::statement("
                    UPDATE links_externos le
                    JOIN global_links gl ON le.link = gl.link AND le.ID_Vendedor = gl.ID_Vendedor
                    SET le.global_link_id = gl.id
                    WHERE le.global_link_id IS NULL
                ");
            }
        }

        // 4. Limpeza Segura (AQUI ESTAVA O ERRO)
        Schema::table('links_externos', function (Blueprint $table) {

            // Garante índice simples para 'id_organizacao' antes de mexer
            $indexExists = collect(DB::select("SHOW INDEX FROM links_externos WHERE Key_name = 'links_externos_id_organizacao_index'"))->count() > 0;
            if (!$indexExists) {
                $table->index('id_organizacao', 'links_externos_id_organizacao_index');
            }

            // --- SOLUÇÃO DO ERRO 1091: Busca o nome real da FK ---
            if (Schema::hasColumn('links_externos', 'ID_Vendedor')) {
                $dbName = DB::connection()->getDatabaseName();
                $fkName = DB::table('information_schema.KEY_COLUMN_USAGE')
                    ->where('TABLE_SCHEMA', $dbName)
                    ->where('TABLE_NAME', 'links_externos')
                    ->where('COLUMN_NAME', 'ID_Vendedor')
                    ->where('REFERENCED_TABLE_NAME', 'Vendedores')
                    ->value('CONSTRAINT_NAME');

                if ($fkName) {
                    $table->dropForeign($fkName);
                }
            }

            // Tenta remover o índice único antigo (se existir)
            try {
                $table->dropUnique('idx_link_unico');
            } catch (\Exception $e) {
            }

            // Remove colunas duplicadas
            $columnsToDrop = [];
            if (Schema::hasColumn('links_externos', 'link')) $columnsToDrop[] = 'link';
            if (Schema::hasColumn('links_externos', 'ID_Vendedor')) $columnsToDrop[] = 'ID_Vendedor';
            if (Schema::hasColumn('links_externos', 'data_ultima_verificacao')) $columnsToDrop[] = 'data_ultima_verificacao';
            if (Schema::hasColumn('links_externos', 'status_link')) $columnsToDrop[] = 'status_link';

            if (!empty($columnsToDrop)) {
                $table->dropColumn($columnsToDrop);
            }
        });

        // 5. Novo índice único
        $newIndexExists = collect(DB::select("SHOW INDEX FROM links_externos WHERE Key_name = 'idx_org_global_link'"))->count() > 0;
        if (!$newIndexExists) {
            Schema::table('links_externos', function (Blueprint $table) {
                $table->unique(['id_organizacao', 'global_link_id'], 'idx_org_global_link');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('links_externos', 'global_link_id')) {
            Schema::table('links_externos', function (Blueprint $table) {
                $table->dropForeign(['global_link_id']);
                $table->dropColumn('global_link_id');
            });
        }
        Schema::dropIfExists('global_links');
    }
};
