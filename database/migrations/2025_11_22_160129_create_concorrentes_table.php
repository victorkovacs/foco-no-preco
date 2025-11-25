<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('concorrentes', function (Blueprint $table) {
            // ⚠️ CORREÇÃO CRÍTICA: Remover $table->id() e definir o campo 'id'
            // sem ser Primary Key ainda, para que possamos definir a PK composta.
            $table->bigInteger('id')->unsigned()->autoIncrement();

            $table->unsignedBigInteger('id_organizacao')->nullable(false);

            $table->integer('id_alvo')->nullable();

            // Correção B: NOT NULL
            $table->integer('id_link_externo')->nullable(false);

            $table->string('sku', 50)->nullable();
            $table->integer('ID_Vendedor')->nullable();
            $table->decimal('preco', 10, 2)->nullable();

            // Coluna de particionamento (NOT NULL)
            $table->dateTime('data_extracao')->nullable(false);

            // ⚠️ CORREÇÃO CRÍTICA: Define a PRIMARY KEY COMPOSTA
            // Incluindo a coluna de particionamento (data_extracao) e o id.
            $table->primary(['id', 'id_organizacao', 'data_extracao']);

            // Índices secundários
            $table->index(['id_organizacao', 'data_extracao']);
            $table->index(['id_organizacao', 'sku', 'data_extracao']);
            $table->index(['id_alvo', 'data_extracao']);
        });

        // Particionamento (O comando DB::statement pode permanecer o mesmo)
        $sql = "
            ALTER TABLE concorrentes 
            PARTITION BY RANGE (YEAR(data_extracao) * 100 + MONTH(data_extracao)) (
                PARTITION p_202401 VALUES LESS THAN (202402),
                PARTITION p_202402 VALUES LESS THAN (202403),
                PARTITION p_202403 VALUES LESS THAN (202404),
                PARTITION p_202404 VALUES LESS THAN (202405),
                PARTITION p_202405 VALUES LESS THAN (202406),
                PARTITION p_202406 VALUES LESS THAN (202407),
                PARTITION p_202407 VALUES LESS THAN (202408),
                PARTITION p_202408 VALUES LESS THAN (202409),
                PARTITION p_202409 VALUES LESS THAN (202410),
                PARTITION p_202410 VALUES LESS THAN (202411),
                PARTITION p_202411 VALUES LESS THAN (202412),
                PARTITION p_202412 VALUES LESS THAN (202501),
                PARTITION p_202501 VALUES LESS THAN (202502),
                PARTITION p_202502 VALUES LESS THAN (202503),
                PARTITION p_202503 VALUES LESS THAN (202504),
                PARTITION p_202504 VALUES LESS THAN (202505),
                PARTITION p_202505 VALUES LESS THAN (202506),
                PARTITION p_202506 VALUES LESS THAN (202507),
                PARTITION p_202507 VALUES LESS THAN (202508),
                PARTITION p_202508 VALUES LESS THAN (202509),
                PARTITION p_202509 VALUES LESS THAN (202510),
                PARTITION p_202510 VALUES LESS THAN (202511),
                PARTITION p_202511 VALUES LESS THAN (202512),
                PARTITION p_202512 VALUES LESS THAN (202601),
                PARTITION p_202601 VALUES LESS THAN (202602),
                PARTITION p_max VALUES LESS THAN MAXVALUE
            );
        ";
        DB::statement($sql);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('concorrentes');
    }
};
