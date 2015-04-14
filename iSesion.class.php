<?php
$root = (!isset($root)) ? "../" : $root;
require_once($root."librerias/Cookies.class.php"); 
require_once($root."librerias/MySQL.class.php");
require_once($root."librerias/Validaciones.class.php");
require_once($root."librerias/Fechas.class.php");
require_once($root."librerias/Historial.class.php"); 
require_once($root."modulos/usuarios/librerias/Usuarios.class.php");
/**
 * Copyright (c) 2015, Jose Alexis Correa valencia
 * Except as otherwise noted, the content of this library  is licensed under the Creative Commons 
 * Attribution 3.0 License, and code samples are licensed under the Apache 2.0 License.
 * @link http://creativecommons.org/licenses/by/3.0/
 *
 * THE WORK (AS DEFINED BELOW) IS PROVIDED UNDER THE TERMS OF THIS CREATIVE COMMONS 
 * PUBLIC LICENSE ("CCPL" OR "LICENSE"). THE WORK IS PROTECTED BY COPYRIGHT AND/OR OTHER 
 * APPLICABLE LAW. ANY USE OF THE WORK OTHER THAN AS AUTHORIZED UNDER THIS LICENSE OR 
 * COPYRIGHT LAW IS PROHIBITED. BY EXERCISING ANY RIGHTS TO THE WORK PROVIDED HERE, 
 * YOU ACCEPT AND AGREE TO BE BOUND BY THE TERMS OF THIS LICENSE. TO THE EXTENT THIS 
 * LICENSE MAY BE CONSIDERED TO BE A CONTRACT, THE LICENSOR GRANTS YOU THE RIGHTS 
 * CONTAINED HERE IN CONSIDERATION OF YOUR ACCEPTANCE OF SUCH TERMS AND CONDITIONS.
 * @link http://creativecommons.org/licenses/by-nd/3.0/us/legalcode
 * 
 *  php - iCharts
 * Esta clase puede incrustar gráficos en una página web con Google Charts API. Puede generar 
 * HTML y JavaScript para realizar llamadas a la API de Google Charts para mostrar varios tipos de 
 * gráficos estadísticos. Actualmente soporta la incrustación de gráficos de tipo pastel, columna, 
 * área, línea, barras, burbujas, marcadores geográficos y caída libre.
 * @author Jose Alexis Correa Valencia <insside@facebook.com> 
 * @package iGoogle 
 * @see http://code.google.com/apis/chart/ 
 * @see http://code.google.com/apis/ajax/playground/?type=visualization 
 */

class Sesion {
  /**
 * Esta clase debe ser lo mas independiente en lo posible de otras, ya que por si misma al invocar internamente 
 * a otra clase que requiera de ella misma generara un error por redundancia ciclica como se enuncia a continuación
 * y que en su logica puede resultar algo de dificil comprensión expresado por el php como
 * Maximum function nesting level of '100' reached, aborting!
 */
  var $cookies;
  var $consola,$estado;
  var $validaciones;
  var $fechas;
  var $historial;
  
  function Sesion() {
    $this->estado=@session_start();
    $this->validaciones=new Validaciones();
    $this->cookies=new Cookies();
    $this->fechas=new Fechas();
    $this->historial=new Historial();
    if($this->estado){
      if(!isset($_SESSION['sid'])){
        $this->inicializar();
      }else{
        $this->actualizar();
      }
    }else{

    }
  }

  function inicializar() {
    @session_cache_limiter('private_no_expire');
    @session_cache_expire(2592000);
    $this->registrar('sid',strtoupper(session_id()));
    $this->registrar('usuario',"ANONIMO");
    $this->registrar('rol',"NINGUNO");
    $this->registrar('fecha',date('Y-m-d',time()));
    $this->registrar('inicio',date('H:i:s',time()));
    $this->registrar('finalizacion',date('H:i:s',strtotime("00:00:00")));
    $this->registrar('actualizada',date('H:i:s',time()));
    $this->registrar('limite',session_cache_limiter());
    $this->registrar('expiracion',session_cache_expire());
    $this->registrar('ip',$_SERVER['REMOTE_ADDR']);
  }

  function actualizar() {
    $_SESSION['actualizada']=date('H:i:s',time());
  }

  function consultar($dato) {
    if($dato=="usuario"){
      $r=@$_SESSION['usuario'];
      if((empty($r)||$r=="ANONIMO")&&(isset($_REQUEST['usuario']))){
        $r=@$_REQUEST['usuario'];
      }
    }else{
      $r=@$_SESSION[$dato]; 
    } return($r);
  }
  
  /**
   * Registra datos en la sesión y las cookies.
   * @param type $dato
   * @param type $valor
   */
  function registrar($dato,$valor) {
    @$_SESSION[$dato]=$valor;
    @$_COOKIE[$dato]=$valor;
  }
  /**
   * 
   * @param type $dato
   * @param type $valor
   */
  function descartar($dato) {
    unset($_SESSION[$dato]);
    $this->cookies->Delete($dato);
  }
  
  
  /**
   * 
   */
  function finalizar() { 
    $usuario=$this->usuario();
    $this->historial-> set_Salir($usuario['usuario'],"000","EXITO",$sql="");
    $this->cookies->Wipe();
    session_destroy();
    $this->registrar('usuario',"ANONIMO");
    
  }

  /**    Carga todos los permisos asociados a un rol, al conjunto de permisos    estipulados para un rol se le denomina politica (existe una tabla para este proposito) .   * */
  function politicas($rol) {
    $db=new MySQL();
    $sql="SELECT * FROM `aplicacion_politicas` WHERE `rol` = '".$rol."' ORDER BY `permiso`;";
    $consulta=$db->sql_query($sql);
    $cantidad_politicas=$db->sql_numrows($consulta);
    if(intval($cantidad_politicas)>0){
      while($fila=$db->sql_fetchrow($consulta)) {
        $this->registrar($fila['permiso'],true);
      }
    } $db->sql_close();
  }

  /** 	
   * Autenticar:El proceso de autenticacion es el proceso mediante el cual un usuario,   		
   * cuya identidad ha sido verificada, obitene todos los permisos asociados en   
   * las politicas de sus roles, para comodidad del sistema esposible asignar varios roles   
   * a un mismo usuario, ya que si un permiso especifico esta duplicado esto resultara   
   * ineherente en el proceso. 
   * 
   * @param type $alias
   */
  function autenticar($alias) {
    $db=new MySQL();
    $consulta=$db->sql_query("SELECT * FROM `usuarios_usuarios` WHERE(`alias` = '".$alias."');");
    $fila=$db->sql_fetchrow($consulta);
    $this->registrar('usuario',$fila['usuario']);
    $this->registrar('alias',$fila['alias']);
    $consulta=$db->sql_query("SELECT * FROM `usuarios_jerarquias` WHERE(`usuario` = '".$fila['usuario']."');");
    while($fila=$db->sql_fetchrow($consulta)) {
      $this->politicas($fila['rol']);
    } $db->sql_close();
  }

  /** 	Analizar:visualiza completamente el contenido del objeto sesion exponiendo el contenido   * 		del vector.   * */
  function analizar() {
    echo("<pre>");
    print_r($_SESSION);
    echo("</pre>" );
  }

  /**
   * Esta funcion consulta los datos del usuario activo, si la interfaz esta desplegada y por algun motivo
   * el usuario no esta activo, toma la variable UID presente en la transacción para estabecer el usuario actual
   * activo y responsable de la transacción. La respuesta es un vector que contiene los datos del usuario
   * @return type Array
   */
  function usuario() {
    $su=@$_SESSION['usuario'];
    $uid=@$_REQUEST['uid'];
    $usuario=($su!="ANONIMO")?$su:$uid;
    $db = new MySQL();
    $sql="SELECT * FROM `usuarios_usuarios` WHERE(`usuario` = '" . $usuario ."');";
    $consulta = $db->sql_query($sql);
    $fila = $db->sql_fetchrow($consulta);
    $db->sql_close();
    return($fila);
  }

}

$sesion=new Sesion();
?>