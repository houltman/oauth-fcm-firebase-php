<?php
require 'vendor/autoload.php';

use Google\Auth\Credentials\ServiceAccountCredentials;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\RequestException;

// Credenciales
$credentialsPath = 'test-c456a-7d79783f6d97.json';

// Alcance necesario para FCM
$scopes = ['https://www.googleapis.com/auth/cloud-platform'];

$tokenFile = 'token.json';
$tokeExpiration = 5 * 60 * 60; // 5 horas
//$tokeExpiration = 30; // 30 segundos

// Función para obtener un nuevo token de acceso
function getAccessToken($credentialsPath, $scopes)
{
    $credentials = new ServiceAccountCredentials(
        $scopes,
        $credentialsPath
    );
    return $credentials->fetchAuthToken();
}

try {
    // Si el archivo existe
    if (file_exists($tokenFile)) {
        $accessTokenData = json_decode(file_get_contents($tokenFile), true);
        // Verificar si el token ha expirado
        if (isset($accessTokenData['expires_at']) && time() >= $accessTokenData['expires_at']) {
            // El token ha expirado crea uno nuevo y actualiza el archivo
            $newAccessToken = getAccessToken($credentialsPath, $scopes);
            $newAccessToken['expires_at'] = time() + $tokeExpiration;
            file_put_contents($tokenFile, json_encode($newAccessToken));
            $accessToken = $newAccessToken['access_token'];
        } else {
            // Token aún válido
            $accessToken = $accessTokenData['access_token'];
        }
    } else {
        // Archivo no existe, crear uno nuevo y actualiza el token
        $newAccessToken = getAccessToken($credentialsPath, $scopes);
        $newAccessToken['expires_at'] = time() + $tokeExpiration;
        file_put_contents($tokenFile, json_encode($newAccessToken));
        $accessToken = $newAccessToken['access_token'];
    }
} catch (Exception $e) {
    echo 'Error cargando el token: ',  $e->getMessage(), "\n";
    exit();
}

try {
    // Usa el token de acceso para enviar la notificación
    $httpClient = new GuzzleClient([
        'base_uri' => 'https://fcm.googleapis.com/v1/',
        'headers' => [
            'Authorization' => 'Bearer ' . $accessToken,
            'Content-Type' => 'application/json',
        ]
    ]);

    $message = [
        'message' => [
            'token' => 'dNzCEVylQtyCuC2HiCaMp7:APA91bEk3okWlPPKhIsguefhw-BuABO5JXrarPVFVRbejcf6YUNDFyjPQeNeej4YVUVS8LYf81jfgsrqzJOJ_SzfgEYgHvca1hqaCgLeTfCm1FUxJcRRzoR99EPAXvMnKOrDEh6pzJa4', // Reemplaza con el token del dispositivo
            //'notification' => [
            //    'title' => 'Hello World',
            //    'body' => 'This is a test notification.',
            //],
            'data' => [
                'title' => 'Activacion',
                "subtitle" => "Activación de linea",
                "message" => "*Activacion100 exitosa* ICC:8952050012204520710F",
            ],
        ],
    ];

    $response = $httpClient->post('https://fcm.googleapis.com/v1/projects/test-c456a/messages:send', [
        'json' => $message
    ]);

    $responseBody = $response->getBody();

    echo $responseBody;
} catch (RequestException $e) {
    echo 'Error enviando la notificación: ',  $e->getMessage(), "\n";
} catch (Exception $e) {
    echo 'Error general: ',  $e->getMessage(), "\n";
}
