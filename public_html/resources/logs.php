<?php

date_default_timezone_set('Europe/Lisbon');

function writeLoginLog($message, $level = 'INFO') {
    $logFile = __DIR__ . '../../../logs/loginLogs.log';
    $currentDateTime = date('Y-m-d H:i:s');
    $logMessage = "[$currentDateTime] $level: $message" . PHP_EOL;

    file_put_contents($logFile, $logMessage, FILE_APPEND | LOCK_EX);
}

function writeAdminLog($message, $level = 'INFO') {
    $adminFile = __DIR__ . '../../../logs/administradorLogs.log';
    $currentDateTime = date('Y-m-d H:i:s');
    $logMessage = "[$currentDateTime] $level: $message" . PHP_EOL;

    file_put_contents($adminFile, $logMessage, FILE_APPEND | LOCK_EX);
}

function writeUtilizadorLog($message, $level = 'INFO') {
    $utilizadorFile = __DIR__ . '../../../logs/utilizadorLogs.log';
    $currentDateTime = date('Y-m-d H:i:s');
    $logMessage = "[$currentDateTime] $level: $message" . PHP_EOL;

    file_put_contents($utilizadorFile, $logMessage, FILE_APPEND | LOCK_EX);
}

function cleanOldLogs($logFile, $adminFile, $utilizadorFile, $days = 30) {
    if (!file_exists($logFile)) {
        return;
    }

    $lines = file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $linesAdmin = file($adminFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $linesUtilizador = file($utilizadorFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $newLogs = [];
    $threshold = strtotime("-$days days");

    foreach ($lines as $line) {
        preg_match('/\[(.*?)\]/', $line, $matches);
        if (!empty($matches[1]) && strtotime($matches[1]) > $threshold) {
            $newLogs[] = $line;
        }
    }

    foreach ($linesAdmin as $line) {
        preg_match('/\[(.*?)\]/', $line, $matches);
        if (!empty($matches[1]) && strtotime($matches[1]) > $threshold) {
            $newLogs[] = $line;
        }
    }

    foreach ($linesUtilizador as $line) {
        preg_match('/\[(.*?)\]/', $line, $matches);
        if (!empty($matches[1]) && strtotime($matches[1]) > $threshold) {
            $newLogs[] = $line;
        }
    }

    file_put_contents($logFile, implode(PHP_EOL, $newLogs) . PHP_EOL);
    file_put_contents($adminFile, implode(PHP_EOL, $newLogs) . PHP_EOL);
    file_put_contents($utilizadorFile, implode(PHP_EOL, $newLogs) . PHP_EOL);
}

function shouldCleanLogs($markerFile) {
    $lastRun = file_exists($markerFile) ? file_get_contents($markerFile) : 0;
    $currentTime = time();

    if ($currentTime - $lastRun > 86400) {
        file_put_contents($markerFile, $currentTime);
        return true;
    }

    return false;
}
?>
