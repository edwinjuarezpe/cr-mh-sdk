# CR MH SDK — XAdES-EPES

SDK en PHP para **firmar XML** de comprobantes electrónicos de **Ministerio de Hacienda (Costa Rica)** usando **XMLDSig + XAdES-EPES** (RSA + SHA-256 por defecto).

## Características
- Firma **enveloped** (`ds:Signature` dentro del XML).
- Perfil **XAdES-EPES** con `SignaturePolicyIdentifier` de MH.
- Soporta **SHA1 / SHA256 / SHA384 / SHA512**.
- Firma a partir de **archivo XML** o **string XML**.
- Incluye `X509Data` y `RSAKeyValue` desde el `.p12`.

## Requisitos
- PHP **^8.1**
- Extensiones: `ext-dom`, `ext-libxml`, `ext-openssl`
- Certificado **PKCS#12 (.p12)** con certificado y clave privada

## Instalación
```bash
composer require edwinjuarezpe/cr-mh-sdk
```

## Uso rápido

### 1) Firmar **archivo** XML
```php
<?php
require __DIR__ . '/vendor/autoload.php';

use EdwinJuarez\Mh\Signing\XadesEpesSigner;
use EdwinJuarez\Mh\Signing\Algorithm;

$certPath       = __DIR__ . '/examples/cert/mi-certificado.p12';
$certPassword   = 'mi_password';
$unsignedXml    = __DIR__ . '/examples/xml_unsigned/mi_comprobante.xml';
$signedXmlPath  = __DIR__ . '/examples/xml_signed/mi_comprobante.xml';

$signer   = new XadesEpesSigner(Algorithm::sha256());
$signed   = $signer->sign($certPath, $certPassword, $unsignedXml);

file_put_contents($signedXmlPath, rtrim($signed, PHP_EOL));
echo "OK: firmado en $signedXmlPath", PHP_EOL;
```

### 2) Firmar **string** XML
```php
<?php
require __DIR__ . '/vendor/autoload.php';

use EdwinJuarez\Mh\Signing\XadesEpesSigner;
use EdwinJuarez\Mh\Signing\Algorithm;

$certPath     = __DIR__ . '/examples/cert/mi-certificado.p12';
$certPassword = 'mi_password';

$xml = file_get_contents(__DIR__ . '/examples/xml_unsigned/mi_comprobante.xml');

$signer = new XadesEpesSigner(Algorithm::sha256());
$signed = $signer->sign($certPath, $certPassword, $xml);

echo $signed; // o guardar con file_put_contents(...)
```

## Compatibilidad de comprobantes
La firma es **enveloped** sobre el documento completo. Funciona para los tipos de MH (Factura, Tiquete Electrónico, Nota débito/crédito, etc.) siempre que el XML siga los **namespaces** y XSD oficiales. La validación fiscal final depende de los **esquemas de MH**.

## Estructura relevante
- `src/Signing/XadesEpesSigner.php` — Lógica XAdES-EPES.
- `src/Signing/Algorithm.php` — Hash/URI y `OPENSSL_ALGO_*`.
- `src/Signing/Pkcs12.php` — Carga y parseo del `.p12`.
- `examples/` — Scripts de ejemplo (`sign_file.php`, `sign_string.php`).

## Licencia
[MIT](https://opensource.org/license/mit)

## Autor
**Edwin Juarez C.**  
Web: https://www.edwin-juarez.com/  
LinkedIn: https://linkedin.com/in/edwin-juarez-c-7018681b9

## ☕ Donación
Si este proyecto te ayuda, invítame una taza de café:  
**PayPal:** edwinjuarez24x@gmail.com
