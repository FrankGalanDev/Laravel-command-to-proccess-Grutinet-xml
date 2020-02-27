<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Filesystem;
use Illuminate\Http\File;

class Importar extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'importar';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Importa el xml de Grutinet,lo convierte  a JSON y coloca los archivos en el servidor';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        
     
     
        
    //tomamos la fecha del sistema
         setlocale(LC_TIME,"spanish");  
         
    //dd($hora);
        $dia = date("m.d.y");
        $hora = date("H.i.s");

    // dd($fecha_hoy);
        $filepath = 'http://media.grutinet.com/ficheros/ListaArticulosD-500.xml';
     
    //fijamos la hora a la que queremos iniciar el proceso de importacion para que el scheduler la vea
    
    /*
        if ($hora === "13:16")
          {
        
            $guardar = Storage::disk('importados')->put($dia.'_'.$hora.'_nuevogrutinet.xml', file_get_contents($filepath));
  
          } 
    */
        $guardar = Storage::disk('importados')->put($dia.'_'.$hora.'_nuevogrutinet.xml', file_get_contents($filepath));

    
    //extraemos los files del disco 
        $archivo = \Storage::disk('importados')->files();
        //dd($archivo);
    
        $contador =   count($archivo);
        //dd($contador); 
    
    // escogemos el ultimo archivo de la lista
        $ultimo = end($archivo);
        //dd($ultimo);

    //obtenemos la url del archivo
        $url = Storage::url($ultimo);
        //dd($url);
    
        $store = storage_path($ultimo);
        //dd($store);

    //  $tamaño = Storage::size($contenido);
      //   dd($tamaño);

  //obtenemos el tipo de archivo
        $tipo = pathinfo($url , PATHINFO_EXTENSION);
        //dd($tipo);

  // cargamos el archivo con get
        $contenido = \Storage::disk('importados')->get($ultimo); 
        //dd($contenido);



  //Determinamos Id del proveedor
  //INICIO FUNCION PARA DETERMINAR ID DEL PROVEEDOR 
    
        
        $proveedor = $filepath;
        if($guardar)
          {

  //\b indica limite de la palabra, por lo que solo la palabra grutinet se compara y no una palabra parcial como grutinet.co o esgrutinet
            if(preg_match("/\bgrutinet\b/", $filepath))
                $proveedor = 1;
              }  
              
            if(preg_match("/\blove\b/", $filepath))
              {
                $proveedor = 2;
              }

            else
              {
                $proveedor = 3; 
              };
         
      
  //FINAL FUNCION PARA DETERMINAR ID DEL PROVEEDOR 


  //insertamos en nuestra bd los datos de la ultima importacion  
        $insertar_importacion = DB::table('importaciones')->insert([
                  'estados_id' => 3,
                  'users_id' => 1,
                  'proveedores_id' => $proveedor,
                  'nombre' => $ultimo,
                  'url_archivo'  => $url,
                  'tipo_archivo'  =>$tipo,
                  'tamaño'  =>'1',
                  
                 ]);

    
  //convertimos el archivo en un objeto xml
        $nuevoxml = new \SimpleXMLElement($contenido);
    
  // recorremos el xml con foreach para aquella parte del archivo que nos interesa,  utilizando children para acceder a los nodos,  con strip_tags, eliminamos las etiquetas html y php, que puede traer el archivo, ademas aplicamops a explicacion un preg_replace para cambiar las comillas por espacios en blanco, es comun que en este objeto añadan texto que inclueyn comillas dobles, si eso cambiara habria que revisar el error 

        foreach ($nuevoxml->ArticulosD->children() as $item)
            {
          
                $codigos[] = strip_tags($item->codigo);
                $familias[]  = strip_tags($item->familia); 
                $subfamilias[]  = strip_tags($item->subfamilia); 
                $eans[]  = strip_tags($item->ean); 
                $tallas[]  = strip_tags($item->talla); 
                $descripcionoris[]  = strip_tags($item->descripcionori); 
                $precio_recomendados[]  = strip_tags($item->precio_recomendado); 
                $ivas[]  = strip_tags($item->iva); 
                $explicacions[]  = strip_tags(preg_replace("[\"]","",($item->explicacion))); 
                $explicacion_textos[]  = strip_tags($item->explicacion_texto); 
                $stock_disponibles[]  = strip_tags($item->stock_disponible); 
                $fabricantes[]  = strip_tags($item->fabricante); 
                $imagen_bus[]  = strip_tags($item->imagen_bu); 
            } 
    
  // le damos formato a los datos, organizando las variables dentro de un array       
        $articulosImportado="[";
    
  //recorremos el array con un for para comenzar a crear el json, añadimos comillas y eliminamos las que no nos interesan
        for($i=0;$i<sizeof($codigos);$i++)
        //  for($i=560;$i</*sizeof($codigos)*/14000;$i++)  
             
            {      
                $articulosImportado.= "{".'"codigo"'.":".'"'.$codigos[$i].'"'."," ;
                $articulosImportado.= '"familia"'.":".'"'.$familias[$i].'"'.","; 
                $articulosImportado.= '"subfamilia"'.":".'"'.$subfamilias[$i].'"'.",";
                $articulosImportado.= '"ean"'.":".'"'.$eans[$i].'"'.",";
                $articulosImportado.= '"talla"'.":".'"'.$tallas[$i].'"'.",";
                $articulosImportado.= '"descripcionori"'.":".'"'.preg_replace("[\"|\n|\r|\n\r|\t]","",trim($descripcionoris[$i])).'"'.",";
                $articulosImportado.= '"precio_recomendado"'.":".$precio_recomendados[$i].",";
                $articulosImportado.= '"iva"'.":".$ivas[$i].",";
                $articulosImportado.= '"explicacion"'.":".'"'./*preg_replace("[\"|\n|\r|\n\r|\t]","",*/ trim($explicacions[$i]/*)*/).'"'.",";
                $articulosImportado.= '"explicacion_texto"'.":".'"'.preg_replace("[\"|\n|\r|\n\r|\t]","", trim($explicacion_textos[$i])).'"'.",";
                $articulosImportado.= '"stock_disponible"'.":".$stock_disponibles[$i].",";
                $articulosImportado.= '"fabricante"'.":".'"'.$fabricantes[$i].'"'.",";
                $articulosImportado.= '"imagen_bu"'.":".'"'.$imagen_bus[$i].'"'."}".",";
            }
        
        $articulosImportado.="]";
       
  //eliminamos los saltos de linea, retornos del carro y tabulaciones
        $articulosImportado = preg_replace("[\n|\r|\n\r|\t]","", $articulosImportado);


  //eliminamos la coma final y el cierre de corchete
        $articulosImportado = substr($articulosImportado, 0, -2);

  //volvemos a añadir el cierra de corchete
        $listafinal =  $articulosImportado."]";
  
  // dd($listafinal[452]);
       
  // convertimos a json
        $filejson = json_decode($listafinal);  
    
  //dd($filejson[2]->codigo); 
    
      
  //creamos la funcion para extraer los duplicados, colocando en un array temporal los valores unicos
  //INICIO FUNCION DUPLICADOS      
        function duplicados($arreglo, $llave)
          {
            $key_array = array();
            $i = 0;
            // echo (gettype($arreglo));

            if ($arreglo)
                {
                //echo sizeof($arreglo);
                
                //iniciamos el for  para todo el array, los valores unicos se guardan en array temporal
                for($k=0;$k<sizeof($arreglo);$k++)
                    {
                          
                        if (!in_array($arreglo[$k]->$llave, $key_array))
                            {
                                $key_array[$i] = $arreglo[$k]->$llave;
                                // echo ($key_array);
                                $array_temporal[$i] = $arreglo[$k]; 
                                $i++;
                            }
                                //  echo $arreglo[$k]->$llave.'<br>';
                         
                    } 
           
                //var_dump($array_temporal);
                return $array_temporal;
                }; 
          } 
  //FINAL FUNCION DUPLICADOS

  //aplicamos  la funcion a nuestro json
        $file_s_duplic  = duplicados($filejson, 'codigo');  
        //dd($file_s_duplic[2]->codigo );    
     
  //codifico para json y que pueda se guardado
        $file_s_duplic_encode = json_encode($file_s_duplic); 
   
       
  //guardamos con la fecha de hoy
        Storage::disk('listos')->put($dia.'_'.$hora.'_listo.json',   $file_s_duplic_encode);
    
    


    }
}
