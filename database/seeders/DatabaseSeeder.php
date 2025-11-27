<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
// Se quiser usar o Model Organizacao aqui, importe: use App\Models\Organizacao;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Criar Organização (Respeitando colunas: nome_empresa, ativa, plano)
        // Usando DB::table para garantir inserção direta ou Model se preferir
        $idOrganizacao = DB::table('Organizacoes')->insertGetId([
            'nome_empresa' => 'Foco no Preço Matriz', // CORRIGIDO
            'plano' => 'enterprise',
            'ativa' => 1,
            'data_cadastro' => now(),
        ]);

        // 2. Criar Usuário Admin vinculado
        User::create([
            'id_organizacao' => $idOrganizacao,
            'email' => 'v.jesus.k@gmail.com',
            'senha_hash' => Hash::make('kovacs1234'),
            'nivel_acesso' => 1, // Mestre
            'ativo' => true,
        ]);
    }
}
