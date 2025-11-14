#!/usr/bin/php
<?php
/*
Script PHP version 8.4 simples que executa o servidor embutido (PHP -S) em segundo plano. 
O script recebe comandos via linha de comando: "start" para iniciar o servidor e "stop" para parar. 
Ele usa um arquivo de PID para gerenciar o processo do servidor.

Observações:
- O servidor embutido é adequado para desenvolvimento, não para produção.
- O script assume que o PHP está disponível no PATH como php.
- Substitua localhost e 8000 conforme necessário.
- O script cria um console PID em server.pid para permitir stop/kill do processo.
*/

const CONFIG_FILE = __DIR__ . DIRECTORY_SEPARATOR . 'config.json';

function loadConfig(): array {
    if (!file_exists(CONFIG_FILE)) {
        // fallback simples caso o config.json não exista
        return [
            "PID_FILE" => __DIR__ . DIRECTORY_SEPARATOR . 'identy' . DIRECTORY_SEPARATOR . 'server.pid',
            "LOG_FILE" => __DIR__ . DIRECTORY_SEPARATOR . 'log' . DIRECTORY_SEPARATOR . 'server.log',
            "HOST" => 'localhost',
            "PORT" => 8090,
            "DOC_ROOT" => __DIR__ . DIRECTORY_SEPARATOR . 'public'
        ];
    }

    $content = @file_get_contents(CONFIG_FILE);
    if ($content === false) {
        throw new RuntimeException("Não foi possível ler config.json");
    }
    $cfg = @json_decode($content, true);
    if (!is_array($cfg)) {
        throw new RuntimeException("config.json inválido");
    }

    // Normaliza caminhos para evitar problemas
    foreach (['PID_FILE', 'LOG_FILE', 'DOC_ROOT'] as $key) {
        if (isset($cfg[$key]) && is_string($cfg[$key])) {
            $cfg[$key] = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $cfg[$key]);
        }
    }

    // Assegura diretórios existem (cria se não existir)
    foreach (['PID_FILE', 'LOG_FILE', 'DOC_ROOT'] as $pathKey) {
        if (isset($cfg[$pathKey])) {
            $path = $cfg[$pathKey];
            $dir = is_dir($path) ? $path : dirname($path);
            if (!is_dir($dir)) {
                @mkdir($dir, 0755, true);
            }
        }
    }

    return $cfg;
}

function isRunning(array $cfg): bool {
    $pidFile = $cfg['PID_FILE'];
    if (!file_exists($pidFile)) return false;
    $pid = @file_get_contents($pidFile);
    if ($pid === false || $pid === '') return false;
    $pid = (int) trim($pid);
    if ($pid <= 0) return false;

    // Verifica se o processo ainda existe
    $ret = @exec("kill -0 $pid 2>/dev/null", $out, $status);
    return $status === 0;
}

function startServer(array $cfg): void {
    if (isRunning($cfg)) {
        echo "Servidor já está rodando. PID já existente no arquivo.\n";
        return;
    }

    $HOST = $cfg['HOST'];
    $PORT = (int) $cfg['PORT'];
    $DOC_ROOT = $cfg['DOC_ROOT'];
    $LOG_FILE = $cfg['LOG_FILE'];

    // Garante diretório do log
    $logDir = dirname($LOG_FILE);
    if (!is_dir($logDir)) {
        @mkdir($logDir, 0755, true);
    }

    $pidFile = $cfg['PID_FILE'];
    $cmd = sprintf(
        "nohup php -S %s:%d -t %s >> %s 2>&1 & echo $!",
        escapeshellarg($HOST),
        $PORT,
        escapeshellarg($DOC_ROOT),
        escapeshellarg($LOG_FILE)
    );

    $pid = @shell_exec($cmd);

    if ($pid === null || trim($pid) === '') {
        echo "Falha ao iniciar o servidor.\n";
        return;
    }

    file_put_contents($pidFile, trim($pid));
    echo "Servidor iniciado (PID {$pid}). Log em server.log\n";
}

function stopServer(array $cfg): void {
    $pidFile = $cfg['PID_FILE'];
    if (!file_exists($pidFile)) {
        echo "PID file não encontrado. O servidor pode não estar rodando.\n";
        return;
    }

    $pidStr = @file_get_contents($pidFile);
    if ($pidStr === false || trim($pidStr) === '') {
        echo "PID inválido.\n";
        return;
    }
    $pid = (int) trim($pidStr);

    if ($pid <= 0) {
        echo "PID inválido.\n";
        return;
    }

    $sig = 15; // SIGTERM
    $ok = @posix_kill($pid, $sig);
    if (!$ok) {
        @exec("kill -9 $pid 2>/dev/null", $out, $status);
        if ($status !== 0) {
            echo "Falha ao parar o servidor. PID: {$pid}\n";
            return;
        }
    }

    if (file_exists($pidFile)) {
        unlink($pidFile);
    }
    echo "Servidor parado (PID {$pid}).\n";
}

// Carrega configuração
$cfg = loadConfig();

// Validação simples de argumento
$arg = $argv[1] ?? '';

switch (strtolower($arg)) {
    case 'start':
        startServer($cfg);
        break;
    case 'stop':
        stopServer($cfg);
        break;
    default:
        echo "Uso: php serve.php [start|stop]\n";
        break;
}