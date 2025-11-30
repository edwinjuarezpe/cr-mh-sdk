
---

# 📦 CR MH SDK – Firma XAdES-EPES + Envío + Consulta (Costa Rica)

SDK en PHP para **firmar**, **enviar** y **consultar** comprobantes electrónicos del **Ministerio de Hacienda de Costa Rica**, utilizando:

* **XAdES-EPES** (firma enveloped)
* **XMLDSig RSA + SHA256**
* **API de Recepción /recepcion** y **estado por clave**
* Autenticación mediante **IdP OAuth2 (grant type: password)**

Diseñado para ser **simple**, **estable**, **seguro** y **multi-compañía**.

---

## ✨ Características principales

### 🔐 Firma XAdES-EPES

* Firma **enveloped** (`<ds:Signature>`) dentro del XML.
* Perfil **XAdES-EPES** con `SignaturePolicyIdentifier` de MH.
* Compatible con SHA-1 / SHA-256 / SHA-384 / SHA-512.
* Firma a partir de **archivo XML** o **string XML**.
* Compatible con: **Factura**, **Tiquete**, **ND**, **NC**, etc.

### 🚀 Envío a la API de Recepción

* POST `/recepcion` con XML firmado Base64.
* Manejo de códigos **202**, **400**, **401**, **403**, **50X**.
* Obtiene la URL de consulta desde el **Location** del header.

### 📡 Consulta de estado por clave

* GET `/recepcion/{clave}`
* Normalización de estados:

  * `aceptado`
  * `rechazado`
  * `pendiente`
* Incluye acuse XML base64 del rechazo/aceptación (si viene).
* Opcionalmente extrae:

  * `mensaje`
  * `estadoMensaje`
  * `detalleMensaje`

### 🔑 Autenticación OAuth2 IdP MH

* No fuerza almacenamiento de token.
* Por defecto usa **NullTokenStore** (no persiste tokens).
* Puedes activar:

  * `MemoryTokenStore` (memoria)
  * `FileTokenStore` (persistente con locks seguros)
  * O crear tu propio store (`TokenStoreInterface`).

---

## 📦 Instalación

```bash
composer require edwinjuarezpe/cr-mh-sdk
```

---

## ⚙️ Requisitos

* PHP **>= 8.1**
* Extensiones:

  * `ext-dom`
  * `ext-libxml`
  * `ext-openssl`
* Certificado **PKCS#12 (.p12)** válido para firmar XML

---

# 🧩 Uso

---

# 1️⃣ Firmar XML – XAdES-EPES

### ✔ Firmar **archivo** XML

```php
use EdwinJuarez\Mh\Signing\XadesEpesSigner;
use EdwinJuarez\Mh\Signing\Algorithm;

$signer = new XadesEpesSigner(Algorithm::sha256());

$certPath     = __DIR__ . '/cert/mi-cert.p12';
$certPassword = '1234';
$unsignedXml  = __DIR__ . '/xml_unsigned/factura.xml';

$signed = $signer->sign($certPath, $certPassword, $unsignedXml);

file_put_contents(__DIR__ . '/xml_signed/factura_firmada.xml', $signed);
```

### ✔ Firmar **string** XML

```php
$xml = file_get_contents('factura.xml');
$signed = $signer->sign($certPath, $certPassword, $xml);
```

---

# 2️⃣ Enviar XML firmado a Hacienda

```php
use EdwinJuarez\Mh\Mh;
use EdwinJuarez\Mh\Config\Settings;
use EdwinJuarez\Mh\Dto\SubmitOptions;

// 1) Configuración
$settings = new Settings(
    env:               'stag',
    baseUrlApi:        'https://api-sandbox.comprobanteselectronicos.go.cr/recepcion/v1',
    baseUrlIdp:        'https://idp.comprobanteselectronicos.go.cr/auth/realms/rut-stag',
    clientId:          'api-stag',
    username:          'usuario@stag.comprobanteselectronicos.go.cr',
    password:          'MI_PASSWORD',
    timeoutSec:        20,
    connectTimeoutSec: 5,
    userAgent:         'CR-MH-SDK/1.0'
);

// 2) Cliente (NullTokenStore por defecto)
$client = Mh::clientWithPassword($settings);

// 3) Opciones
$opts = new SubmitOptions(
    callbackUrl: null,
    consecutivoReceptor: null
);

// 4) Enviar
$ack = $client->submitXml($signedXml, $opts);

echo "HTTP: {$ack->httpStatus}\n";
echo "Location: {$ack->location}\n";
echo "Clave: {$ack->clave}\n";
```

---

# 3️⃣ Consultar estado por clave

```php
$st = $client->statusByClave($ack->clave);

echo "Estado: {$st->estado}\n";

if ($st->mensaje)   echo "Mensaje: {$st->mensaje}\n";
if ($st->estadoMensaje)   echo "EstadoMensaje: {$st->estadoMensaje}\n";
if ($st->detalleMensaje)  echo "DetalleMensaje: {$st->detalleMensaje}\n";

if ($st->acuseXmlBase64) {
    file_put_contents('acuse.xml', base64_decode($st->acuseXmlBase64));
}
```

---

# 🗃 Opcional: Usar FileTokenStore (persistente)

```php
use EdwinJuarez\Mh\Config\StoragePathResolver;
use EdwinJuarez\Mh\Infrastructure\Store\FileTokenStore;

$resolver = new StoragePathResolver(__DIR__ . '/storage/mh_tokens');
$baseDir  = $resolver->resolveBaseDir();
$dataDir  = $resolver->dataDir($baseDir);
$locksDir = $resolver->locksDir($baseDir);

$store = new FileTokenStore($dataDir, $locksDir);

$client = Mh::clientWithPassword($settings, $store);
```

---

# 📚 Documentación oficial MH

* Anexos y Estructuras
  [https://atv.hacienda.go.cr/ATV/ComprobanteElectronico/frmAnexosyEstructuras.aspx](https://atv.hacienda.go.cr/ATV/ComprobanteElectronico/frmAnexosyEstructuras.aspx)

---
## Licencia
[MIT](https://opensource.org/license/mit)

---
# 🧑‍💻 Autor

**Edwin Juarez C.**
🌐 [https://www.edwin-juarez.com](https://www.edwin-juarez.com)
💼 [https://linkedin.com/in/edwin-juarez-c-7018681b9](https://linkedin.com/in/edwin-juarez-c-7018681b9)

---

# ☕ Donación

Si este proyecto te ayuda, puedes invitarme un café:
**PayPal:** `edwinjuarez24x@gmail.com`

---
