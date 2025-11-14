<?php
$site_esta_online = true; // Mude para true para ver a página principal
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Página em Construção</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="container">
        <?php if (!$site_esta_online): ?>
            <h1 class="titulo-padrao">Manutenção!</h1>
            <div class="spinner" aria-label="Carregando"></div>
            <p>Volte em breve para mais novidades.</p>
            <p>Desculpe o transtorno.</p>
        <?php else: ?>
            <!-- Aqui entraria o conteúdo do seu site principal -->
            <h1 class="titulo-padrao">Bem-vindo ao site!</h1>
            <p>Este é o conteúdo principal.</p>
        <?php endif; ?>
    </div>
</body>
</html>