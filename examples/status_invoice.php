<?php

require __DIR__ . '/../vendor/autoload.php';

use EdwinJuarez\Mh\Mh;
use EdwinJuarez\Mh\Config\Settings;
use EdwinJuarez\Mh\Dto\SubmitOptions;
use EdwinJuarez\Mh\Signing\Algorithm;
use EdwinJuarez\Mh\Signing\XadesEpesSigner;
use EdwinJuarez\Mh\Exceptions\AuthException;
use EdwinJuarez\Mh\Exceptions\MhApiException;
use EdwinJuarez\Mh\Config\StoragePathResolver;
use EdwinJuarez\Mh\Exceptions\ConfigException;
use EdwinJuarez\Mh\Exceptions\TransportException;
use EdwinJuarez\Mh\Infrastructure\Store\FileTokenStore;
use EdwinJuarez\Mh\Infrastructure\Store\MemoryTokenStore;

// 1) Configuración por compañía (usa tus valores reales)
$settings = new Settings(
    env:               'stag', // o 'prod'
    baseUrlApi:        'https://api-sandbox.comprobanteselectronicos.go.cr/recepcion/v1',
    baseUrlIdp:        'https://idp.comprobanteselectronicos.go.cr/auth/realms/rut-stag',
    clientId:          'api-stag',
    username:          'usuario@stag.comprobanteselectronicos.go.cr',
    password:          'MI_PASSWORD',
    timeoutSec:        20,
    connectTimeoutSec: 5,
    userAgent:         'CR-MH-SDK/1.0'
);

// 2) Cliente ya cableado (No se pasa TokenStore, usa NullTokenStore por defecto)
// $client = Mh::clientWithPassword($settings);

// // 2) Cliente con TokenStore en memoria (NO persistente)
// $store  = new MemoryTokenStore();

// $client = Mh::clientWithPassword($settings, $store);

// 2) Cliente con TokenStore en archivos (persistente)
$tokensDir = __DIR__ . '/storage/mh_tokens/';
$resolver = new StoragePathResolver($tokensDir);
$baseDir  = $resolver->resolveBaseDir();
$dataDir  = $resolver->dataDir($baseDir);
$locksDir = $resolver->locksDir($baseDir);

$fileStore = new FileTokenStore($dataDir, $locksDir);

$client = Mh::clientWithPassword($settings, $fileStore);

try {
    // 3) Consultar estado por clave
    $clave = '50629112500020453045200100001010000000192100000777';
    $st = $client->statusByClave($clave);
    
    echo "HTTP: {$st->httpStatus}\n";
    echo "Clave: {$st->clave}\n";
    echo "Estado: {$st->estado}\n";
    echo "Fecha: {$st->fecha}\n";

    // Guardar acuse si viene
    if ($st->acuseXmlBase64) {
        if ($st->mensaje) {
          echo "Mensaje: {$st->mensaje}\n";
        }
  
        if ($st->estadoMensaje) {
          echo "EstadoMensaje: {$st->estadoMensaje}\n";
        }
  
        if ($st->detalleMensaje) {
            echo "DetalleMensaje: {$st->detalleMensaje}\n";
        }

        $out = __DIR__ . '/xml_response/' . $st->clave . '_respuesta.xml';
        file_put_contents($out, base64_decode($st->acuseXmlBase64));
        echo "Acuse guardado en: $out\n";
    }

} catch (AuthException $e) {
    echo "[AUTH] {$e->getMessage()}\n";
} catch (ConfigException $e) {
    echo "[CONFIG] {$e->getMessage()}\n";
} catch (MhApiException $e) {
    echo "[MH API] {$e->getMessage()}\n";
} catch (TransportException $e) {
    echo "[HTTP] {$e->getMessage()}\n";
} catch (\Throwable $e) {
    echo "[ERROR] {$e->getMessage()}\n";
}
