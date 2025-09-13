<?php  

use EdwinJuarez\Mh\Signing\Algorithm;
use EdwinJuarez\Mh\Signing\XadesEpesSigner;

require __DIR__ . '/../vendor/autoload.php';

$xmlString = <<<XML
<?xml version="1.0" encoding="utf-8"?>
<FacturaElectronica xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns="https://cdn.comprobanteselectronicos.go.cr/xml-schemas/v4.4/facturaElectronica">
  <Clave>50613092500012345678900100001010000000185100000777</Clave>
  <ProveedorSistemas>123456789</ProveedorSistemas>
  <CodigoActividadEmisor>721001</CodigoActividadEmisor>
  <NumeroConsecutivo>00100001010000000185</NumeroConsecutivo>
  <FechaEmision>2025-09-13T07:46:16-06:00</FechaEmision>
  <Emisor>
    <Nombre><![CDATA[EMISOR NOMBRE PRUEBA]]></Nombre>
    <Identificacion>
      <Tipo>01</Tipo>
      <Numero>123456789</Numero>
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
      <Numero>999999999</Numero>
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

$signer = new XadesEpesSigner(Algorithm::sha256());

$certPath = "./cert/certificate.p12";
$certPassword = "12345";

$signedXmlPath = "./xml_signed/Signed_FacturaElectronica.xml";

$signedXml = $signer->sign($certPath, $certPassword, $xmlString);

file_put_contents($signedXmlPath, $signedXml);

echo "OK: signed XML saved to $signedXmlPath", PHP_EOL;
exit();


