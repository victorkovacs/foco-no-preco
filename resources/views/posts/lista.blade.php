<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Lista de Artigos</title>
</head>
<body>
    <h1>Artigos Recentes</h1>

    @if (count($posts) > 0)
        {{-- Itera sobre a coleção de posts --}}
        @foreach ($posts as $post)
            <div>
                <h2>{{ $post->titulo }}</h2>
                <p>{{ $post->conteudo }}</p>
                <small>Criado em: {{ $post->created_at }}</small>
                <hr>
            </div>
        @endforeach
    @else
        <p>Ainda não há posts cadastrados!</p>
    @endif
    
</body>
</html>