<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Post;

class PaginasController extends Controller
{
    //aqui vão ficar todas as paginas

    public function sobre(){

        $nomeapp = 'kovacs';
        $versao = 1.0;
        $parceiro = 'parceiro program';


        return view('sobre', compact('nomeapp', 'versao', 'parceiro'));
    }

    public function produto($id){

        return view('produto', compact('id'));

    }

    public function listarPosts()
    {
        $posts = Post::all(); 
        
         return view('posts.lista', compact('posts'));
    }
}
