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

$xmlString = <<<XML
<?xml version="1.0" encoding="utf-8"?>
<FacturaElectronica xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns="https://cdn.comprobanteselectronicos.go.cr/xml-schemas/v4.4/facturaElectronica">
  <Clave>50629112500020453045200100001010000000192100000777</Clave>
  <ProveedorSistemas>204530452</ProveedorSistemas>
  <CodigoActividadEmisor>721001</CodigoActividadEmisor>
  <NumeroConsecutivo>00100001010000000192</NumeroConsecutivo>
  <FechaEmision>2025-11-29T20:50:05-06:00</FechaEmision>
  <Emisor>
    <Nombre><![CDATA[EMISOR NOMBRE PRUEBA]]></Nombre>
    <Identificacion>
      <Tipo>01</Tipo>
      <Numero>204530452</Numero>
    </Identificacion>
    <NombreComercial><![CDATA[EMISOR NOMBRE COMERCIAL PRUEBA]]></NombreComercial>
    <Ubicacion>
      <Provincia>2</Provincia>
      <Canton>10</Canton>
      <Distrito>01</Distrito>
      <Barrio>San Rafael</Barrio>
      <OtrasSenas><![CDATA[CIUDAD QUESADA]]></OtrasSenas>
    </Ubicacion>
    <Telefono>
      <CodigoPais>506</CodigoPais>
      <NumTelefono>12345678</NumTelefono>
    </Telefono>
    <CorreoElectronico>emisorx@gmail.com</CorreoElectronico>
  </Emisor>
  <Receptor>
    <Nombre><![CDATA[RECEPTOR NOMBRE PRUEBA]]></Nombre>
    <Identificacion>
      <Tipo>01</Tipo>
      <Numero>105110120</Numero>
    </Identificacion>
    <NombreComercial><![CDATA[RECEPTOR NOMBRE COMERCIAL PRUEBA]]></NombreComercial>
    <Ubicacion>
      <Provincia>2</Provincia>
      <Canton>10</Canton>
      <Distrito>01</Distrito>
      <Barrio>San Antonio</Barrio>
      <OtrasSenas><![CDATA[CIUDAD QUESADA]]></OtrasSenas>
    </Ubicacion>
    <Telefono>
      <CodigoPais>506</CodigoPais>
      <NumTelefono>12345678</NumTelefono>
    </Telefono>
  </Receptor>
  <CondicionVenta>01</CondicionVenta>
  <DetalleServicio>
    <LineaDetalle>
      <NumeroLinea>1</NumeroLinea>
      <CodigoCABYS>4523000000000</CodigoCABYS>
      <CodigoComercial>
        <Tipo>04</Tipo>
        <Codigo>00000001</Codigo>
      </CodigoComercial>
      <Cantidad>1.000</Cantidad>
      <UnidadMedida>Unid</UnidadMedida>
      <Detalle>ARTICULO DE PRUEBA1</Detalle>
      <PrecioUnitario>120.00000</PrecioUnitario>
      <MontoTotal>120.00000</MontoTotal>
      <Descuento>
        <MontoDescuento>20.00000</MontoDescuento>
        <CodigoDescuento>06</CodigoDescuento>
        <NaturalezaDescuento>DESCUENTO POR PROMOCION</NaturalezaDescuento>
      </Descuento>
      <SubTotal>100.00000</SubTotal>
      <BaseImponible>100.00000</BaseImponible>
      <Impuesto>
        <Codigo>01</Codigo>
        <CodigoTarifaIVA>08</CodigoTarifaIVA>
        <Tarifa>13.00</Tarifa>
        <Monto>13.00000</Monto>
      </Impuesto>
      <ImpuestoAsumidoEmisorFabrica>0.00</ImpuestoAsumidoEmisorFabrica>
      <ImpuestoNeto>13.00000</ImpuestoNeto>
      <MontoTotalLinea>113.00000</MontoTotalLinea>
    </LineaDetalle>
    <LineaDetalle>
      <NumeroLinea>2</NumeroLinea>
      <CodigoCABYS>4523000000000</CodigoCABYS>
      <CodigoComercial>
        <Tipo>04</Tipo>
        <Codigo>00000002</Codigo>
      </CodigoComercial>
      <Cantidad>2.000</Cantidad>
      <UnidadMedida>Unid</UnidadMedida>
      <Detalle>ARTICULO DE PRUEBA2</Detalle>
      <PrecioUnitario>50.00000</PrecioUnitario>
      <MontoTotal>100.00000</MontoTotal>
      <Descuento>
        <MontoDescuento>10.00000</MontoDescuento>
        <CodigoDescuento>06</CodigoDescuento>
        <NaturalezaDescuento>DESCUENTO POR PROMOCION</NaturalezaDescuento>
      </Descuento>
      <SubTotal>90.00000</SubTotal>
      <BaseImponible>90.00000</BaseImponible>
      <Impuesto>
        <Codigo>01</Codigo>
        <CodigoTarifaIVA>08</CodigoTarifaIVA>
        <Tarifa>13.00</Tarifa>
        <Monto>11.70000</Monto>
      </Impuesto>
      <ImpuestoAsumidoEmisorFabrica>0.00</ImpuestoAsumidoEmisorFabrica>
      <ImpuestoNeto>11.70000</ImpuestoNeto>
      <MontoTotalLinea>101.70000</MontoTotalLinea>
    </LineaDetalle>
  </DetalleServicio>
  <ResumenFactura>
    <CodigoTipoMoneda>
      <CodigoMoneda>CRC</CodigoMoneda>
      <TipoCambio>1.00000</TipoCambio>
    </CodigoTipoMoneda>
    <TotalMercanciasGravadas>220.00000</TotalMercanciasGravadas>
    <TotalGravado>220.00000</TotalGravado>
    <TotalVenta>220.00000</TotalVenta>
    <TotalDescuentos>30.00000</TotalDescuentos>
    <TotalVentaNeta>190.00000</TotalVentaNeta>
   <TotalDesgloseImpuesto>
      <Codigo>01</Codigo>
      <CodigoTarifaIVA>08</CodigoTarifaIVA>
      <TotalMontoImpuesto>24.70000</TotalMontoImpuesto>
   </TotalDesgloseImpuesto>
    <TotalImpuesto>24.70000</TotalImpuesto>
    <MedioPago>
      <TipoMedioPago>01</TipoMedioPago>
      <TotalMedioPago>214.70000</TotalMedioPago>
    </MedioPago>
    <TotalComprobante>214.70000</TotalComprobante>
  </ResumenFactura>
  <Otros>
    <OtroTexto codigo="string1"><![CDATA[Otro Texto Prueba]]></OtroTexto>
    <OtroTexto codigo="string2"><![CDATA[Otro Texto2 Prueba]]></OtroTexto>
    <OtroContenido codigo="ContactoDesarrollador"><![CDATA[edwinjuarez24x@gmail.com]]></OtroContenido>
  </Otros>
</FacturaElectronica>
XML;

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

// 3) Firmar el XML con XAdES-EPES
$signer = new XadesEpesSigner(Algorithm::sha256());

$certPath = "./cert/020453045208-Stag.p12";
$certPassword = "8690";

$signedXmlPath = __DIR__ . '/xml_signed/Signed_FacturaElectronica.xml';

$signedXml = $signer->sign($certPath, $certPassword, $xmlString);

file_put_contents($signedXmlPath, $signedXml);

// // 3) Cargar XML firmado XAdES-EPES
// $xmlPath = __DIR__ . '/xml_signed/factura_firmada.xml';
// $signedXml = file_exists($xmlPath) ? file_get_contents($xmlPath) : '';

try {
    if ($signedXml === '') {
        throw new \RuntimeException("No se encontró el XML firmado en: $xmlPath");
    }

    // (Opcional)
    $opts = new SubmitOptions(
        callbackUrl: null,
        consecutivoReceptor: null
    );

    // 4) Enviar a /recepcion → 202 (Location con la URL de consulta)
    $ack = $client->submitXml($signedXml, $opts);
    echo "Enviado. HTTP={$ack->httpStatus}\n";
    echo "Location: {$ack->location}\n";
    echo "Clave: {$ack->clave}\n";

    // 5) Consultar estado por clave
    $st = $client->statusByClave($ack->clave);
    echo "Estado: {$st->estado}\n";

    // 6) Guardar acuse si viene
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
