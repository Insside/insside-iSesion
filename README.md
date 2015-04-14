# Clase iSesion - Insside Framewok®

# Bienvenido al insside-iSesion wiki!
Clase administrativa para el manejo de sesiones. Si bien existen numerosos artículos escritos 
sobre el tema es minuciosamente difícil encontrar información útil a partir de una sola fuente. 
Por esta razón mas que una discusión sobre las diversas técnicas que se usan para aumentar la 
seguridad de una sesión es que eh decidido articular abiertamente el control del manejo de 
sesiones mediante la implementación de una única clase cuya función será centralizar los 
procedimientos y métodos para el inicio de sesión de la plataforma con el objetivo de prevenir el 
secuestro de sesión y los ataques a fuerza bruta. Siendo consciente de que no existen métodos 
infalibles para la prevención de las múltiples estrategia de ataque, esta clase busca incrementar 
el grado de dificultad y la prudencia en la realización de los diversos procesos asociados se 
aceptan los comentarios, sugerencias, críticas, y ejemplos de código de lectores como usted, 
ya que benefician a la comunidad en su conjunto, para el crecimiento de la plataforma Insside®. 

### Objetivos: 
La sencillez, solidez.
### Características:
* Todo se almacena en el servidor no confiamos en los datos del lado del cliente, (ni siquiera la fecha de caducidad de la cookie de sesión).
* Las direcciones IP se comprueban en cada acceso para evitar el secuestro de las cookies de sesión como habitualmente hacen programas tipo Firesheep.
* La Sesión expira ante la inactividad y fecha de caducidad es prolongada con la interacción del usuario.
* Una clave secreta y aleatoria se genera en el lado del servidor para cada sesión esta se puede utilizar para firmar los formularios (HMAC).
* Utilización de Tokens para prevenir ataques XSRF.
* Protección contra ataques a fuerza bruta con la gestión de prohibiciones.

### Notas:
* Es aconsejable remplazar el uso de  globals con las variables de la clase iSesión

Copyright (c) 2015, Jose Alexis Correa valencia
Except as otherwise noted, the content of this library  is licensed under the Creative Commons 
Attribution 3.0 License, and code samples are licensed under the Apache 2.0 License.
@link http://creativecommons.org/licenses/by/3.0/

THE WORK (AS DEFINED BELOW) IS PROVIDED UNDER THE TERMS OF THIS CREATIVE COMMONS 
PUBLIC LICENSE ("CCPL" OR "LICENSE"). THE WORK IS PROTECTED BY COPYRIGHT AND/OR OTHER 
APPLICABLE LAW. ANY USE OF THE WORK OTHER THAN AS AUTHORIZED UNDER THIS LICENSE OR 
COPYRIGHT LAW IS PROHIBITED. BY EXERCISING ANY RIGHTS TO THE WORK PROVIDED HERE, 
YOU ACCEPT AND AGREE TO BE BOUND BY THE TERMS OF THIS LICENSE. TO THE EXTENT THIS 
LICENSE MAY BE CONSIDERED TO BE A CONTRACT, THE LICENSOR GRANTS YOU THE RIGHTS 
CONTAINED HERE IN CONSIDERATION OF YOUR ACCEPTANCE OF SUCH TERMS AND CONDITIONS.
@link http://creativecommons.org/licenses/by-nd/3.0/us/legalcode

