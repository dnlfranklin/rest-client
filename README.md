# Rest Client

Gerenciador de requisições HTTP utilizando cURL

## Instalação

Para instalar esta dependência através do [Composer](https://getcomposer.org/).
```shell
composer require bonuscred/rest-client
```

## Utilização

```php
$req = new RestClient\Request;
$res = $req->get('https://api.github.com/repos/dnlfranklin/rest-client');

echo $res->getHeaderLine('content-type');
echo $res->get_http_code();
echo $res->get_data(); 
```

### Request

#### Métodos

| Nome do método | Descrição |
|--------|-----------|
|baseUrl|URL base|
|buildIndexedQueries|Customiza parâmetros: "key[0]=foo&key[1]=bar" para "key[]=foo&key[]=bar". Só é aplicado se o requestFormat não for chamado|
|basic|Adiciona ao header o authorization do tipo basic|
|bearer|Adiciona ao header o atributo authorization do tipo bearer|
|requestFormat|Informa o formato da requisição que está sendo enviado. Ex: json|
|responseFormat|Informa o formato do response que será recebido. Ex: json|
|userAgent|Adiciona ao header o atributo user-agent|
|sslCert|Adiciona nas opções de cURL o nome de um arquivo que contém um certificado no formato PEM (CURLOPT_SSLCERT) e o nome de um arquivo que contém uma chave SSL privada (CURLOPT_SSLKEY).
|userPwd|Adiciona nas opções de cURL os parâmetros (usuário e senha) de autenticação (CURLOPT_USERPWD)|
|header|Adiciona header que será usado em todas as requisições HTTP|
|curlOption|Adiciona uma opção de cURL|
|get|Realiza uma chamada HTTP com método GET|
|post|Realiza uma chamada HTTP com método POST|
|put|Realiza uma chamada HTTP com método PUT|
|patch|Realiza uma chamada HTTP com método PATCH|
|delete|Realiza uma chamada HTTP com método DELETE|
|head|Realiza uma chamada HTTP com método HEAD|
|execute|Realiza uma chamada HTTP|

#### Chamadas HTTP

Todas as chamadas HTTP retornam um objeto Response.

##### Chamada com método estático
```php
$res = new RestClient\Request::run('https://api.github.com/repos/dnlfranklin/rest-client', 'GET'); // Objeto Response
```

##### Callback hooks
```php
$req = new RestClient\Request;

// Callback chamado em caso em requisições bem sucedidas
$req->onSuccess(function(Response $response){
    echo 'Success: '.$response->get_http_code();
});

// Callback chamado em caso de erro de requisição
$req->onError(function(Response $response){
    echo 'Error: '.$response->get_errmessage();
});

// Callback chamado após finalização de requisição
$req->onComplete(function(Response $response){
    echo $response->get_body();
});

$res = $req->get('https://api.github.com/repos/dnlfranklin/rest-client');
```

#### Concatenação de métodos

```php
$req = new RestClient\Request;

$res_data = $req->baseUrl('https://api.github.com')
                ->buildIndexedQueries(true)
                ->requestFormat('json')
                ->responseFormat('json')
                ->userAgent('Minha API/1.0.0')
                ->onError(function(Response $response){
                    echo 'Error: '.$response->get_errmessage();
                })
                ->get('/repos/dnlfranklin/rest-client')
                ->get_data();            
```

### Response

#### Decode

Suporta atualmente json e xml como auto-decodes, retornando um Array identado dos atributos, caso seja encontado no header (content-type) ou passado via request (responseFormat()).

```php
$res = new RestClient\Request::run('https://api.github.com/repos/dnlfranklin/rest-client');

$data = $res->get_data(); // Array de atributos recebidos no formato json
```

Pode ser formatado através de um callback.

```php
$res = new RestClient\Request::run('https://api.github.com/repos/dnlfranklin/rest-client');

$data = $res->decode(function($body){
    //Tratamento específico de decode
});
```

#### Atributos

É possível acessar todos os dados de requisição/resposta diretamente dos atributos ou via função encapsulada (get_) do objeto Respnse. Lista de atributos disponíveis:

| Atributo | Método encapsulado | Descrição |
|----------|--------------------|-----------|
|url|get_url()|URL da requisição|
|request_method|get_request_method()|Método da requisição|
|request_header|get_request_header()|Cabeçalhos da requisição|
|request_parameters|get_request_parameters()|Corpo da requisição|     
|errno|get_errno()|Retorna o número do erro cURL, se não houver 0 é retornado|
|errmessage|get_errmessage()|Retorna uma string contendo o erro cURL|
|content|get_content()|Conteúdo de resposta cURL ou um boleano caso a opção CURLOPT_RETURNTRANSFER não seja definida|
|headers|get_headers()|Cabeçalhos de resposta indexados|
|body|get_body()|Corpo da resposta|
|data|get_data()|Corpo da resposta formatado como Array indexado caso content-type ou parametros de requisição sejam json ou xml|
|http_code|get_http_code()|Código de resposta HTTP recebido|
|content_type|get_content_type()|Header Content-Type do documento requisitado. NULL indica que o servidor não enviou o cabeçalho Content-Type|
|header_size|get_header_size()|Tamanho total de todos os cabeçalhos recebidos|
|request_size|get_request_size()|Tamanho total de requisições emitidas, atualmente apenas para requisições HTTP|
|filetime|get_filetime()|Horário remoto do documento obtido, com a constante CURLOPT_FILETIME habilitada; se -1 for retornado, o horário do documento é desconhecido|
|ssl_verify_result|get_ssl_verify_result()|Resultado da verificação de certificado SSL requisitada pela habilitação da opção CURLOPT_SSL_VERIFYPEER|
|redirect_count|get_redirect_count()|Número de redirecionamentos, com a opção CURLOPT_FOLLOWLOCATION habilitada|
|total_time|get_total_time()|Tempo total em microssegundos da transferência anterior, including resolução de nome, conexão TCP etc.|
|namelookup_time|get_namelookup_time()|Tempo em microssegundos do início até que a conclusão da resolução de nome|
|connect_time|get_connect_time()|TTempo em microssegundos decorrido do início até que a conexão ao servidor remoto (ou proxy) tenha sido concluída|
|pretransfer_time|get_pretransfer_time()|Tempo decorrido do início até que a transferência de arquivos esteja para iniciar, em microssegundos|
|size_upload|get_size_upload()|Número total de bytes enviados|
|size_download|get_size_download()|Número total de bytes recebidos|
|speed_download|get_speed_download()|Velocidade média de recepção|
|speed_upload|get_speed_upload()|Velocidade média de envio|
|download_content_length|get_download_content_length()|O tamanho do conteúdo recebido. Isto é o valor lido do campo Content-Length:. -1 se o tamanho for desconhecido|
|upload_content_length|get_upload_content_length()|O tamanho especificado do envio. -1 se o tamanho for desconhecido|
|starttransfer_time|get_starttransfer_time()|Tempo em microssegundos decorrido do início até o recebimento do primeiro byte|
|redirect_time|get_redirect_time()|Tempo total em microssegundos decorrido para todas as etapas de redirecionamento incluindo pesquisa de nome, conexão, pré-transferência e transferência, antes do início da transação final|
|redirect_url|get_redirect_url()|Com a opção CURLOPT_FOLLOWLOCATION desabilitada: URL de redirecionamento encontrada na última transação, que deverá ser requisitada manualmente na sequência. Com a opção CURLOPT_FOLLOWLOCATION desabilitada: isto fica vazio. A URL de redirecionamento neste caso fica disponível em CURLINFO_EFFECTIVE_URL|
|primary_ip|get_primary_ip()|Endereço IP da conexão mais recente|
|certinfo|get_certinfo()|Cadeia de certificados TLS|
|primary_port|get_primary_port()|Porta de destino da conexão mais recente|
|local_ip|get_local_ip()|Endereço IP local (origem) da conexão mais recente|
|local_port|get_local_port()|Porta local (origem) da conexão mais recente|
|http_version|get_http_version()|A versão usada na última conexão HTTP. O valor de retorno será uma das constantes CURL_HTTP_VERSION_* definidas ou 0 se a versão não puder ser determinada|
|protocol|get_protocol()|O protocolo usado na última conexão HTTP. O valor retornado será exatamente uma dos valores CURLPROTO_*|
|ssl_verifyresult|get_ssl_verifyresult()|O resultado da verificação de certificado que foi requisitada (usando a opção CURLOPT_PROXY_SSL_VERIFYPEER). Usado apenas para proxy HTTPS|
|scheme|get_scheme()|O esquema de URL usado para a conexão mais recente|
|appconnect_time_us|get_appconnect_time_us()|Tempo em segundos decorrido do início até que a conexão/negociação SSL/SSH ao servidor remoto foi concluída|
|connect_time_us|get_connect_time_us()|Tempo em segundos para estabelecer a conexão|
|namelookup_time_us|get_namelookup_time_us()|Tempo em segundos até que a resolução de nome foi concluída|
|pretransfer_time_us|get_pretransfer_time_us()|Tempo em segundos do início até logo antes de iniciar a transferência de arquivo|
|redirect_time_us|get_redirect_time_us()|Tempo em segundos de todos as etapas de redirecionamento antes do início da transação final, com a opção CURLOPT_FOLLOWLOCATION habilitada|
|starttransfer_time_us|get_starttransfer_time_us()|Tempo em segundos até que o primeiro byte está para ser transferido|
|total_time_us|get_total_time_us()|Tempo total de transação em segundos para a última transferência|


## Requisitos
- PHP 8.0 ou superior
- Pacote libcurl versão 7.29.0 ou superior