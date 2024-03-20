<?php

// Deve-se baixar a biblioteca PHPmailer para utilizar este arquivo, para envio de emails
// Deve-se baixar a biblioteca Dompdf para utilizar este arquivo, para gerar arquivos PDF
// Deve-se baixar a biblioteca Bootstrap para utilizar este arquivo para utilizar a função mensagem

require 'vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;
use Dompdf\Options;
use Dompdf\Dompdf;

define('host', 'localhost');
define('usuario', 'root');
define('senha', '');
define('banco', 'tabela');

// Classe responsavel para conectar o banco de dados ao sistema
// Exemplo: $sistema = new sistema();

class sistema
{

    protected $conexao;
    private $db_host = 'localhost';
    private $db_bd = 'tabela';
    private $db_usuario = 'root';
    private $db_senha = '';
    private $carac = 'utf8';

    function __construct()
        {
            $this->conectado();
        }

    public function conectado()
    {
        try 
        {
            $this->conexao = new PDO("mysql:host=$this->db_host;dbname=$this->db_bd;charset=$this->carac;", $this->db_usuario, $this->db_senha);
            mensagem('success','Conectado com sucesso');
        } 
        catch (PDOException $e) 
        {
            mensagem('danger','Erro ao conectar com o banco de dados: ' .$e->getMessage());
            die();
        }
    }

    public function mostrarTabela($table)
    {
        $selecionar = $this->conexao->query("SELECT * FROM $table");
        while($resultado = $selecionar->fetch(PDO::FETCH_ASSOC))
        {
            $dados = $resultado['nu_id'] . '<br>';
            $dados .= $resultado['no_tipo'] . '<br>';
            $dados .= $resultado['created_at'] . '<br>';
            $dados .= $resultado['updated_at'] . '<br>';
        }
        return $dados;
    }

}

// Classe responsavel para gerar arquivos pdf de uma informação do sistema usando doompdf
// Exemplo: 
// $pdf = new GerarPdf();
// $pdf->CriaPdf(1);
// Onde o numero seria o numero do registro do banco de dados a ser acessado via metodo GET

class GerarPdf
{

    public function CriaPdf($id)
    {
        //INICIALIZAR A CLASSE DO DOMPDF
        $options = new Options();
        $options->set('isRemoteEnabled', true);
        $pdf = new DOMPDF($options);
        //DEFINE ALGUMAS VARIAVEIS PARA APOIAR A GERAÇÃO DAS INFORMAÇÕES DA NOTIFICACAO
        $tipo = 'nome-do-arquivo-';
        $extensao = '.pdf';
        $arquivo = $tipo.$id.$extensao;
        //ALIMENTAR OS DADOS DO PDF
        $html = utf8_encode(file_get_contents(urlarea."arquivo.php?id=".$id));
        //Definir o tamanho do papel e orientação da página
        $pdf->set_paper('A4', 'portrait');
        //CARREGAR O CONTEÚDO HTML
        $pdf->load_html(utf8_decode($html));
        //RENDERIZAR O PDF
        $pdf->render();
        //NOMEAR O PDF GERADO
        $pdf->stream($arquivo, array("Attachment" => true));
    }

}

// Classe responsavel para enviar email usando a classe phpmailer
// Exemplo: 
// $email = new Email();
// $email->Enviar('remetente@site.com.br', 'destinatario@site.com.br', 'Titulo do email', 'Corpo do texto do email', $id);
// Onde a variavel $id seria o numero do registro do banco de dados a ser acessado via metodo GET

class Email
{
    public function Enviar($remetente, $destinatario, $titulo, $corpo, $id)
    {
        $mail = new PHPMailer();
        try 
        {
            // Server settings
            $mail->CharSet = 'UTF-8';
            $mail->isSMTP();                                      // Define o mail para usar o SMTP
            $mail->Host = 'site.com.br';                  // Define o host do e-mail
            $mail->SMTPAuth = true;                               // Permite autenticação SMTP 
            $mail->Username = 'email@site.com.br';          // Conta de e-mail que enviará o e-mail
            $mail->Password = 'senha';                         // Senha da conta de e-mail
            $mail->SMTPSecure = 'ssl';                            // Permite encriptação TLS
            $mail->Port = 465;                                    // Porta TCP que irá se conectar
            // Quem ira receber
            $mail->setFrom($remetente); // Define o remetente
            $mail->addAddress($destinatario);             // Define o destinário
            // Conteudo
            $mail->isHTML(true);            // Define o formato do e-mail para HTML
            $mail->Subject = $titulo;      // Titulo da mensagem
            $mail->Body = $corpo;
            if (!$mail->send()) 
            {
                mensagem('danger','Mensagem de erro: ' . $mail->ErrorInfo);
                return true;
            } 
            else 
            {
               mensagem('success','Email enviado com sucesso');
               redireciona(5, 'postagem.php?id=' . $id);
               return false;
            } 
        } 
        catch (Exception $e)  // Se capturar exceção retorna false
        {
            return false;
        }
    }
   
}    

//  Função para poder remover pastas, subpastas e arquivs dentro de uma pasta em servidores MAC e LINUX, veja o exemplo abaixo de como usar:
//  $removerpasta = clsArquivo::removerDiretorio($pasta_teste, true);
//  if($removerpasta)
//  {
//  	mensagem('success', 'Pasta removida com sucesso');
//  }
//  else
//  {
//	  mensagem('danger', 'Não foi possivel remover a  pasta');
//  }

class clsArquivo
{
 
    public static function pegaExtensao( $nomeArquivo )
    {
        $posicao = strrpos( $nomeArquivo, "." );
        $extensao = strtolower( substr( $nomeArquivo, $posicao + 1 ) );
        return $extensao;
    }
 
 
    public static function listarArquivos( $strDiretorio, $vetExtensoes = null, $bolRecursivo = false, $bolOrdenacaoDecrescente = false )
    {
        if ( is_dir( $strDiretorio ) )
        {
            $vetor = array();
            $strDiretorio .= ( substr( $strDiretorio, -1 ) != "/" ) ? "/" : null;
            $d = dir( $strDiretorio );
            while ( false !== ( $arquivo = $d->read() ) )
            {
                if ( is_dir( $strDiretorio . $arquivo ) )
                {
                    if ( $bolRecursivo && $arquivo != "." && $arquivo != ".." )
                    {
                        $arquivo = $strDiretorio . $arquivo . "/";
                        $vetorResultante = self::listarArquivos( $arquivo, $vetExtensoes, true );
                        if ( is_array( $vetorResultante ) && count( $vetorResultante ) > 0 )
                        {
                            $vetor = array_merge( $vetor, $vetorResultante );
                        }
                    }
                }
                else
                {
                    if ( is_array( $vetExtensoes ) )
                    {
                        $ext = self::pegaExtensao( $arquivo );
                        if ( in_array( $ext, $vetExtensoes ) )
                        {
                            $strArquivo = $strDiretorio . $arquivo;
                            if ( substr( $strArquivo, 0, 2 ) == "./" )
                            {
                                $strArquivo = substr( $strArquivo, 2 );
                            }
                            if ( is_file( $strArquivo ) )
                            {
                                $vetor[] = $strArquivo;
                            }
                        }
                    }
                    else
                    {
                        $strArquivo = $strDiretorio . $arquivo;
                        if ( substr( $strArquivo, 0, 2 ) == "./" )
                        {
                            $strArquivo = substr( $strArquivo, 2 );
                        }
                        if ( is_file( $strArquivo ) )
                        {
                            $vetor[] = $strArquivo;
                        }
                    }
                }
            }
            $d->close();
            if ( count( $vetor ) > 0 )
            {
                if ( $bolOrdenacaoDecrescente )
                {
                    rsort( $vetor );
                }
                else
                {
                    usort( $vetor, 'strnatcasecmp' );
                }
            }
            return $vetor;
        }
 
 
    }
 
 
    public static function removerArquivosDoDiretorio( $strDiretorio, $vetExtensoes = null, $bolBuscaRecursiva = true )
    {
 
        $intSucessos = 0;
 
        $vetArquivos = self::listarArquivos( $strDiretorio, $vetExtensoes, $bolBuscaRecursiva );
        $numArquivos = count( $vetArquivos );
        for ( $i = 0; $i < $numArquivos; $i++ )
        {
            $arquivo = $vetArquivos[$i];
            if ( unlink( $arquivo ) )
            {
                $intSucessos++;
            }
        }
 
        return ($intSucessos == $numArquivos);
 
    }
 
 
    public static function removerDiretorio( $strDiretorio, $bolBuscaRecursiva = true )
    {
        if ( is_dir( $strDiretorio ) )
        {
            self::removerArquivosDoDiretorio( $strDiretorio, null, $bolBuscaRecursiva );
            $objects = scandir( $strDiretorio );
            foreach ( $objects as $object )
            { 
                if ( $object != "." && $object != ".." )
                {
                    if ( filetype( $strDiretorio . "/" . $object ) == "dir" )
                    {
                        rmdir( $strDiretorio . "/" . $object );
                    }
                }
            }
            reset( $objects );
            return rmdir( $strDiretorio );
        }
        return false;
    }
 
}

// Função para converter texto de string para campos decimais (10,2), para posteriormente usar a função formata reais para visualizar
// Exemplo: echo str2dec($variavel-com-a-informação-numerica);
// Salvou no banco de dados com campos decimais
// Mostra do banco de dados as informações formatareais($variavel-com-dados-do-banco-de-dados);

function str2dec($value)
{
    $v = str_replace(['.',','], ['','.'], $value);
    if (is_numeric($v)) return $v;
    return null;
}

// Função para converter qualquer data que esteja no formato errado na data brasileira
// Exemplo: echo data($variavel-com-a-data);

function databr($data){
  return date("d/m/Y", strtotime($data));
}

// Função para converter qualquer data que esteja no formato errado na data brasileira
// Exemplo: echo databr($variavel-com-a-data);

function mensagem($tipo, $mensagem)
{
    echo "<div class='alert alert-{$tipo}'>{$mensagem}</div>";
}

// Função para enviar uma mensagem de exito ou erro de acordo com a situação, necessario biblioteca Bootstrap
// Exemplo1: mensagem'success','Mensagem de sucesso');
// Exemplo2: mensagem('danger','Mensagem de erro');

function redirecionamento($tempo, $dir)
{
    echo "<meta http-equiv='refresh' content='{$tempo}; url={$dir}'>";
}

// Função para redirecionar apos alguma ação, o primeiro valor em segundos, e o segundo o destino
// Exemplo: redirecionamento(5, 'arquivo.php');

function permito($extensao)
{
    $extensoes = ['docx', 'xlsx', 'pdf', 'zip', 'rar', '7z', 'tar'];	 // extensoes permitidas
    if(in_array($extensao, $extensoes))
        return true;
}

// Função para comparar os arquivos jogados em upload e comparar qual arquivo esta na lista permitida
// $arquivo(dados.zip);
// Exemplo: permito($arquivos);

function file_force_contents($filename, $data, $flags = 0)
{
    if(!is_dir(dirname($filename)))
        mkdir(dirname($filename).'/', 0777, TRUE);
    return file_put_contents($filename, $data,$flags);
}

// Função para puxar as informações via http request, dependendo do servidor podera puxar sem curl
// Exemplo: $id = 1
// Exemplo: $pasta = 'arquivos/';
// Exemplo: $barra = '/';
// Exemplo: $tipo_de_funcao = 'notificacao';
// Exemplo: $diretorio = $pasta.$id.$barra.$tipo_de_funcao.$barra;
// Exemplo: file_force_contents($diretorio.'arquivo-'.$id.'.pdf',$dompdf->output()); 
// Resumindo esta função puxou os dados do banco de dados e gerou um arquivo pdf com a biblioteca dompdf

function delTree($dir) 
{ 
    $files = array_diff(scandir($dir), array('.','..')); 
    foreach ($files as $file) { 
      (is_dir("$dir/$file")) ? delTree("$dir/$file") : unlink("$dir/$file"); 
    } 
    return rmdir($dir); 
}

// Função para remover arquivos e pastas gravados nesta função
// Exemplo: delTree('Pasta-Upload');

function url()
{
    $url = "http://$_SERVER[HTTP_HOST]/";
    $url_sistema = explode("//", $url);
        if($url_sistema[1] == 'localhost/')
            {
                $url = "http://$_SERVER[HTTP_HOST]/site/";
            }
}

// Função para converter valores monetarios em variaveis no formato brasileiro monetario, exemplos abaixo:

function formataReais($valor1)
    {
    /*     function formataReais ($valor1, $valor2, $operacao)
    *
    *     $valor1 = Primeiro valor da operação
    *     $valor2 = Segundo valor da operação
    *     $operacao = Tipo de operações possíveis . Pode ser :
    *     "+" = adição,
    *     "-" = subtração,
    *     "*" = multiplicação
    *     "%" = porcentagem
    *
    */
    // Valores testados e aprovados:
    // $exemplo = formataReais("1.000.000,00", "2,00", "%"); --> 20.000,00
    // $exemplo = formataReais("500.000,00", "5,75", "%"); --> 28.750,00
    // $exemplo = formataReais("10,00", "0,82", "%"); --> 0,08
    // $exemplo = formataReais("0,86", "2,00", "%"); --> 0,02

    // echo $exemplo;
    // Antes de tudo , arrancamos os "," e os "." dos dois valores passados a função . Para isso , podemos usar str_replace :
    // Script para substituir vírgula para ponto para valores abaixo de mil que tem o seguinte formato (0,10 - 100,00 - 999,99) ou seja que não tem ponto (resolver um bug de cálculo)
    if (strpos($valor1, ",") !== false && strpos($valor1, ".") === false) {
        $valor1 = str_replace(",", ".", $valor1);
    }
    else {
        $valor1 = str_replace (",", "", $valor1);
        $valor1 = str_replace (".", "", $valor1);
    }

    $resultado = $valor1;

    //Arredondamento de valores que podem surgir nos cálculos como (3.607, 0.512)
    $resultado = round($resultado, 2); // Deixar somente duas casas decimais a frente depois do ponto (.)

    // Forçar entrada de valores 20.4 no "case 4", quando na verdade esses valores deviam ser 20.40 com duas casas a frente
    if (strpos($resultado, ".") !== false) {
        $dopontoadiante = strstr($resultado, "."); //Pegar os valores do ponto adiante
        
        $centavos = str_replace(".", "", $dopontoadiante); //Tirar o ponto e deixar só os centavos '00' ou '04'
        
        $tamCentavos = strlen($centavos);
        if ($tamCentavos == 1) {
            $centavos = $centavos."0"; //Adicionar o segundo zero caso tenha só um centavo de tamanho '0' ao invés de '00'
            
            $real = strstr($resultado, ".", true);
            
            $resultado = $real . $centavos; // se resultado antes era 20.4 agora passou a ser 20.40 ou 2040
        }
    }

    //Criar um script para remover os pontos do resultado (resolvendo o bug do calculo do valor que tem vírgula "0,10 - 100,00 - 999,99" abaixo de mil)
    $resultado = str_replace (",", "", $resultado);
    $resultado = str_replace (".", "", $resultado);

    // Calcula o tamanho do resultado com strlen
    $len = strlen ($resultado);

    // Novamente um switch , dessa vez de acordo com o tamanho do resultado da operação ($len) :
    // De acordo com o tamanho de $len , realizamos uma ação para dividir o resultado e colocar as vírgulas e os pontos
    switch ($len) {
        // 1 : 0,1,2,3,4,5,6,7,8,9 prevenção dos cálculos que retornam números individuais
        case "1":
            $retorna = "$resultado,00";
            break;

        // 2 : 0,99 centavos
        case "2":
            $retorna = "0,$resultado";
            break;

        // 3 : 9,99 reais
        case "3":
            $d1 = substr("$resultado",0,1);
            $d2 = substr("$resultado",-2,2);
            $retorna = "$d1,$d2";
            break;

        // 4 : 99,99 reais
        case "4":
            $d1 = substr("$resultado",0,2);
            $d2 = substr("$resultado",-2,2);
            $retorna = "$d1,$d2";
            break;

        // 5 : 999,99 reais
        case "5":
            $d1 = substr("$resultado",0,3);
            $d2 = substr("$resultado",-2,2);
            $retorna = "$d1,$d2";
            break;

        // 6 : 9.999,99 reais
        case "6":
            $d1 = substr("$resultado",1,3);
            $d2 = substr("$resultado",-2,2);
            $d3 = substr("$resultado",0,1);
            $retorna = "$d3.$d1,$d2";
            break;

        // 7 : 99.999,99 reais
        case "7":
            $d1 = substr("$resultado",2,3);
            $d2 = substr("$resultado",-2,2);
            $d3 = substr("$resultado",0,2);
            $retorna = "$d3.$d1,$d2";
            break;

        // 8 : 999.999,99 reais
        case "8":
            $d1 = substr("$resultado",3,3);
            $d2 = substr("$resultado",-2,2);
            $d3 = substr("$resultado",0,3);
            $retorna = "$d3.$d1,$d2";
            break;
        // 9 : 1.000.000,00 (1 milhão de reais)
        case "9":
            $d1 = substr("$resultado", 0, 1);
            $d2 = substr("$resultado", 1, 3);
            $d3 = substr("$resultado", 4, 3);
            $d4 = substr("$resultado", 7, 2);
            $retorna = "$d1.$d2.$d3,$d4";
            break;
        // 10 : 10.100.000,10 (dez milhões, cem mil e dez centavos)
        case "10":
            $d1 = substr("$resultado", 0, 2);
            $d2 = substr("$resultado", 2, 3);
            $d3 = substr("$resultado", 5, 3);
            $d4 = substr("$resultado", 8, 2);
            $retorna = "$d1.$d2.$d3,$d4";
            break;
        // 11: 100.100.000,10 (cem milhões, cem mil e dez centavos)
        case "11":
            $d1 = substr("$resultado", 0, 3);
            $d2 = substr("$resultado", 3, 3);
            $d3 = substr("$resultado", 6, 3);
            $d4 = substr("$resultado", 9, 2);
            $retorna = "$d1.$d2.$d3,$d4";
            break;
    }

    // Por fim , retorna o resultado já formatado
    return $retorna;

// Valores testados e aprovados:
// $exemplo = formataReais("1.000.000,00", "2,00", "%"); --> 20.000,00
// $exemplo = formataReais("500.000,00", "5,75", "%"); --> 28.750,00
// $exemplo = formataReais("10,00", "0,82", "%"); --> 0,08
// $exemplo = formataReais("0,86", "2,00", "%"); --> 0,02

// echo $exemplo;

}

?>

