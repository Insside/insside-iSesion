<?php

$root = (!isset($root)) ? "../" : $root;

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
 * Class insside-iSesion
 * Clase administrativa para el manejo de sesiones. Si bien existen numerosos artículos escritos 
 * sobre el tema es minuciosamente difícil encontrar información útil a partir de una sola fuente. 
 * Por esta razón mas que una discusión sobre las diversas técnicas que se usan para aumentar la 
 * seguridad de una sesión es que eh decidido articular abiertamente el control del manejo de 
 * sesiones mediante la implementación de una única clase cuya función será centralizar los 
 * procedimientos y métodos para el inicio de sesión de la plataforma con el objetivo de prevenir el 
 * secuestro de sesión y los ataques a fuerza bruta. Siendo consciente de que no existen métodos 
 * infalibles para la prevención de las múltiples estrategia de ataque, esta clase busca incrementar 
 * el grado de dificultad y la prudencia en la realización de los diversos procesos asociados se 
 * aceptan los comentarios, sugerencias, críticas, y ejemplos de código de lectores como usted, 
 * ya que benefician a la comunidad en su conjunto, para el crecimiento de la plataforma Insside®. 
 * 
 * Objetivos: 
 * - La sencillez, solidez.
 * Características:
 * -	Todo se almacena en el servidor no confiamos en los datos del lado del cliente, (ni siquiera la fecha de caducidad de la cookie de sesión).
 * -	Las direcciones IP se comprueban en cada acceso para evitar el secuestro de las cookies de sesión como habitualmente hacen programas tipo Firesheep.
 * -	La Sesión expira ante la inactividad y fecha de caducidad es prolongada con la interacción del usuario.
 * -	Una clave secreta y aleatoria se genera en el lado del servidor para cada sesión esta se puede utilizar para firmar los formularios (HMAC).
 * -	Utilización de Tokens para prevenir ataques XSRF.
 * -	Protección contra ataques a fuerza bruta con la gestión de prohibiciones.
 * Notas:
 * -  Es aconsejable remplazar el uso de  globals con las variables de la clase iSesión
 * Utilización:
 * - @link https://github.com/Insside/insside-iSesion/wiki
 * 
 * @author Jose Alexis Correa Valencia <insside@facebook.com> 
 * @package iSesion 
 * @see https://github.com/Insside/insside-iSesion/wiki
 */

class iSesion {

  // Personnalize PHP session name
  public static $sessionName = 'insside';
  // If the user does not access any page within this time,
  // his/her session is considered expired (3600 sec. = 1 hour)
  public static $inactivityTimeout = 3600;
  // If you get disconnected often or if your IP address changes often.
  // Let you disable session cookie hijacking protection
  public static $disableSessionProtection = false;
  // Ban IP after this many failures.
  public static $banAfter = 4;
  // Ban duration for IP address after login failures (in seconds).
  // (1800 sec. = 30 minutes)
  public static $banDuration = 1800;
  // File storage for failures and bans. If empty, no ban management.
  public static $banFile = '';

  /**
   * Initialize session
   */
  public static function init() {
    self::setCookie();
    // Use cookies to store session.
    ini_set('session.use_cookies', 1);
    // Force cookies for session  (phpsessionID forbidden in URL)
    ini_set('session.use_only_cookies', 1);
    if (!session_id()) {
      // Prevent php to use sessionID in URL if cookies are disabled.
      ini_set('session.use_trans_sid', false);
      if (!empty(self::$sessionName)) {
        session_name(self::$sessionName);
      }
      session_start();
    }
  }

  /**
   * Session set cookie
   *
   * @param integer $lifetime of cookie
   */
  public static function setCookie($lifetime = null) {
    $cookie = session_get_cookie_params();
    // Do not change lifetime
    if ($lifetime === null) {
      $lifetime = $cookie['lifetime'];
    }
    // Force cookie path
    $path = '';
    if (dirname($_SERVER['SCRIPT_NAME']) !== '/') {
      $path = dirname($_SERVER["SCRIPT_NAME"]) . '/';
    }
    // Use default domain
    $domain = $cookie['domain'];
    if (isset($_SERVER['HTTP_HOST'])) {
      $domain = $_SERVER['HTTP_HOST'];
    }
    // Check if secure
    $secure = false;
    if (isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] == "on") {
      $secure = true;
    }
    session_set_cookie_params($lifetime, $path, $domain, $secure);
  }

  /**
   * Returns the IP address
   * (Used to prevent session cookie hijacking.)
   *
   * @return string IP addresses
   */
  private static function _allIPs() {
    $ip = $_SERVER["REMOTE_ADDR"];
    $ip.= isset($_SERVER['HTTP_X_FORWARDED_FOR']) ? '_' . $_SERVER['HTTP_X_FORWARDED_FOR'] : '';
    $ip.= isset($_SERVER['HTTP_CLIENT_IP']) ? '_' . $_SERVER['HTTP_CLIENT_IP'] : '';
    return $ip;
  }

  /**
   * Check that user/password is correct and then init some SESSION variables.
   *
   * @param string $login Login reference
   * @param string $password Password reference
   * @param string $loginTest Login to compare with login reference
   * @param string $passwordTest Password to compare with password reference
   * @param array  $pValues Array of variables to store in SESSION
   * @return true|false True if login and password are correct, false otherwise
   */
  public static function login($login, $password, $loginTest, $passwordTest, $pValues = array()) {
    self::banInit();
    if (self::Acceso()) {
      if ($login === $loginTest && $password === $passwordTest) {
        self::banLoginOk();
        // Generate unique random number to sign forms (HMAC)
        $_SESSION['uid'] = sha1(uniqid('', true) . '_' . mt_rand());
        $_SESSION['ip'] = self::_allIPs();
        $_SESSION['username'] = $login;
        // Set session expiration.
        $_SESSION['expires_on'] = time() + self::$inactivityTimeout;
        foreach ($pValues as $key => $value) {
          $_SESSION[$key] = $value;
        }
        return true;
      }
      self::banLoginFailed();
    }
    return false;
  }

  /**
   * Unset SESSION variable to force logout
   */
  public static function logout() {
    unset($_SESSION['uid'], $_SESSION['ip'], $_SESSION['expires_on']);
  }

  /**
   * Make sure user is logged in.
   *
   * @return true|false True if user is logged in, false otherwise
   */
  public static function isLogged() {
    if (!isset($_SESSION['uid']) || (self::$disableSessionProtection === false && $_SESSION['ip'] !== self::_allIPs()) || time() >= $_SESSION['expires_on']) {
      self::logout();
      return false;
    }
    // User accessed a page : Update his/her session expiration date.
    $_SESSION['expires_on'] = time() + self::$inactivityTimeout;
    if (!empty($_SESSION['longlastingsession'])) {
      $_SESSION['expires_on'] += $_SESSION['longlastingsession'];
    }
    return true;
  }

  /**
   * Create a token, store it in SESSION and return it
   *
   * @param string $salt to prevent birthday attack
   *
   * @return string Token created
   */
  public static function getToken($salt = '') {
    if (!isset($_SESSION['tokens'])) {
      $_SESSION['tokens'] = array();
    }
    // We generate a random string and store it on the server side.
    $rnd = sha1(uniqid('', true) . '_' . mt_rand() . $salt);
    $_SESSION['tokens'][$rnd] = 1;
    return $rnd;
  }

  /**
   * Tells if a token is ok. Using this function will destroy the token.
   *
   * @param string $token Token to test
   *
   * @return true|false   True if token is correct, false otherwise
   */
  public static function isToken($token) {
    if (isset($_SESSION['tokens'][$token])) {
      unset($_SESSION['tokens'][$token]); // Token is used: destroy it.
      return true; // Token is ok.
    }
    return false; // Wrong token, or already used.
  }

  /**
   * Signal a failed login. Will ban the IP if too many failures:
   */
  public static function banLoginFailed() {
    if (self::$banFile !== '') {
      $ip = $_SERVER["REMOTE_ADDR"];
      $gb = $GLOBALS['IPBANS'];
      if (!isset($gb['FAILURES'][$ip])) {
        $gb['FAILURES'][$ip] = 0;
      }
      $gb['FAILURES'][$ip] ++;
      if ($gb['FAILURES'][$ip] > (self::$banAfter - 1)) {
        $gb['BANS'][$ip] = time() + self::$banDuration;
      }
      $GLOBALS['IPBANS'] = $gb;
      file_put_contents(self::$banFile, "<?php\n\$GLOBALS['IPBANS']=" . var_export($gb, true) . ";\n?>");
    }
  }

  /**
   * Signals a successful login. Resets failed login counter.
   */
  public static function banLoginOk() {
    if (self::$banFile !== '') {
      $ip = $_SERVER["REMOTE_ADDR"];
      $gb = $GLOBALS['IPBANS'];
      unset($gb['FAILURES'][$ip]);
      unset($gb['BANS'][$ip]);
      $GLOBALS['IPBANS'] = $gb;
      file_put_contents(self::$banFile, "<?php\n\$GLOBALS['IPBANS']=" . var_export($gb, true) . ";\n?>");
    }
  }

  /**
   * Ban init
   */
  public static function banInit() {
    if (self::$banFile !== '') {
      if (!is_file(self::$banFile)) {
        file_put_contents(self::$banFile, "<?php\n\$GLOBALS['IPBANS']=" . var_export(array('FAILURES' => array(), 'BANS' => array()), true) . ";\n?>");
      }
      include self::$banFile;
    }
  }

  /**
   * Este metodo comprueba si el usuario puede tratar de iniciar sesión evaluando la existencia de 
   * restricciones, en primera instancia evalua si el archivo que contiene el listado de ips a las cuales 
   * se les restringe el acceso se encuentra activo, de no encontrarse definido este archivo por defecto 
   * no existiran restricciones en los intentos de acceso. Si el archivo se ha definido se verifica si la IP
   * hace parte del listado, de hacer parte del listado se evalua si la restricción de acceso a expirado,
   * en tal caso si la restricción expiro se establece que podra intentar iniciar sesión nuevante, caso 
   * contrario la restriccion esta activa, el resultado de la ejecución del metodo determina si se concede
   * la posibilidad de intentar acceder o no al sistem segun el valor retornado.
   * @return boolean true|false.
   */
  public static function Acceso() {
    if (self::$banFile !== '') {
      $ip = $_SERVER["REMOTE_ADDR"];
      $gb = $GLOBALS['IPBANS'];
      if (isset($gb['BANS'][$ip])) {
        if ($gb['BANS'][$ip] <= time()) {
          unset($gb['FAILURES'][$ip]);
          unset($gb['BANS'][$ip]);
          file_put_contents(self::$banFile,"<?php\n\$GLOBALS['IPBANS']=" .var_export($gb, true).";\n?>");
          return(true);
        }
        return(false);
      }
    }
    return(true);
  }

}
?>
