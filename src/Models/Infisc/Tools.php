<?php

namespace NFePHP\NFSe\Models\Infisc;

/**
 * Classe para a comunicação com os webservices da
 * conforme o modelo ISSNET
 *
 * @category  NFePHP
 * @package   NFePHP\NFSe\Models\Infisc\Tools
 * @copyright NFePHP Copyright (c) 2016
 * @license   http://www.gnu.org/licenses/lgpl.txt LGPLv3+
 * @license   https://opensource.org/licenses/MIT MIT
 * @license   http://www.gnu.org/licenses/gpl.txt GPLv3+
 * @author    Roberto L. Machado <linux.rlm at gmail dot com>
 * @link      http://github.com/nfephp-org/sped-nfse for the canonical source repository
 */

use NFePHP\NFSe\Models\Infisc\Rps;
use NFePHP\NFSe\Models\Infisc\Factories;
use NFePHP\NFSe\Common\Tools as ToolsBase;
use NFePHP\Common\Soap\SoapCurl;

class Tools extends ToolsBase
{        
     /**
     * Pedido de teste de envio de lote
     * @param array $rpss
     */
    public function envioLote(array $rpss)
    {
        $this->method = 'ns1:enviarLoteNotas';
        $fact = new Factories\EnviarLoteNotas($this->certificate);
        $fact->setSignAlgorithm($this->algorithm);
        $message = $fact->render(
            $this->versao,            
            $this->CNPJ,
            $this->dhTrans,            
            $rpss
        );
        return $this->sendRequest('', $message);
    }
    
    /**
     * Pedido de status de um lote NFS-e
     * 
     * Esse serviço permite que o contribuinte obtenha a crítica de um lote de NFS-e já enviado.
     * 
     * @param type $lote Número do lote
     * @return type
     */
    public function pedidoStatusLote($lote)
    {
        $this->method = 'ns1:obterCriticaLote';
        $fact = new Factories\PedidoStatusLote($this->certificate);
        $fact->setSignAlgorithm($this->algorithm);
        $message = $fact->render(
            $this->config->versao,
            $this->config->cnpj,
            $lote
        );
        return $this->sendRequest('', $message);
    }
    
    /**
     * Esse serviço permite que o contribuinte solicite as informações de uma NFS-e já submetida
     * 
     * @param type $chave
     * @return type
     */
    public function pedidoNFSe($chave)
    {
        $this->method = 'ns1:obterNotaFiscal';
        $fact = new Factories\PedidoNFSe($this->certificate);
        $fact->setSignAlgorithm($this->algorithm);
        $message = $fact->render(     
            $this->config->versao,
            $this->config->cnpj,
            $chave
        );
        return $this->sendRequest('', $message);
    }
    
    
    protected function sendRequest($url, $message)
    {        
        
        $url = $this->url[$this->config->tpAmb];
        
        $this->soap = new SoapCurl($this->certificate);
        
        //formata o xml da mensagem para o padão esperado pelo webservice
        $dom = new \DOMDocument('1.0', 'UTF-8');
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = true;
        $dom->loadXML($message);
        $message = str_replace('<?xml version="1.0"?>', '<?xml version="1.0" encoding="UTF-8"?>', $dom->saveXML());
        
        $messageText = $message;
        if ($this->withcdata) {
            $messageText = ($message);
        }
        $request = "<{$this->method} soapenv:encodingStyle=\"http://schemas.xmlsoap.org/soap/encoding/\" xmlns:ns1=\"{$this->xmlns}\" >"
            . "<xml xsi:type=\"xsd:string\">$messageText</xml>"
            . "</{$this->method}>";
        $params = [
            'xml' => $message
        ];
        
        $action = "\"". $this->xmlns ."/". $this->method ."\"";
        
        $xml = \NFePHP\Common\Strings::clearXmlString($request);
        $request = preg_replace("/<\?xml.*\?>/", "", $xml);
        //echo '<pre>';
//        header('Content-type: text/xml; charset=UTF-8');
        //error_log(print_r($request, TRUE) . PHP_EOL, 3, '/var/www/tests/sped-nfse/post.xml');       
//        exit();        
        
        return $this->soap->send(
            $url,
            $this->method,
            $action,
            $this->soapversion,
            $params,
            $this->namespaces[$this->soapversion],
            $request
        );                
    }
}