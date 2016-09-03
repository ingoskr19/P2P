# P2P

P2P package

##Requisitos

- 1: Paquete util para realizar conexiones a la base de datos: Illuminate\Database\Eloquent
- 2: Crear una tabla 'transacciones' en la base de datos con los siguientes campos
	- transaccion_id(unico)  int
	- status varchar(255)
	- responseCode       varchar(255)
	- responseReasonCode varchar(255)
	- responseReasonText varchar(255)
	- created_at timestamp
	- updated_at timestamp

los campos created_at y updated_at son agregados automaticamente por la clase Model que trae eloquent

- 3: Tener los datos de acceso a placetopay:
	-tranKey
	-login
	-wsdl


##Metodos de la clase P2P:

    1: public static function getListBanks() 

    2: public static function executeTransaction() 

    3: public static function getTransacciones($atribute=null,$operator=null, $value=null)

    4: public static function verifyEstatusTransactions()

    5: public static function createPerson($atributes)

    6: public static function createPSETransactionRequest($atributes)

    7: public static function createAuth($wsdl, $login, $tranKey, $additional)

    8: private static function callWebService($function,$parameters)



## ORDEN DE LO METODOS PARA USAR:


A) PARA TRAER LA LISTA DE BANCOS DISPONIBLES:

1. LLAMAR EL METODO createAuth($wsdl, $login, $tranKey, $additional) *additional es opcional
2. LLAMAR EL METODO getListBanks() retorna un json con los atributos: bancode, bankname.

B) PARA REALIZAR UNA TRANSACCION:

//el metodo 1 es opcional si ya se hizo el llamado de la lista de bancos.
1. LLAMAR EL METODO createAuth($wsdl, $login, $tranKey, $additional) *additional es opcional
2. LLAMAR EL METODO createPSETransactionRequest($atributes) Pasandole como array los atributos respectivos para realizar una transaction.(usar createPerson($atributes) cada vez para crear datos del pagador, el comprador y cobrador)
3.  LLAMAR EL METODO executeTransaction() 


C) listar transacciones:

1. LLAMAR AL METODO getTransactions($atribute=null,$operator, $value=null) *opcional: si se quiere filtrar por un atributo de la tabla, pasar el nombre del atributo, el tipo de condicional u operador y el valor de filtro.

D)  PARA VERIFICAR ESTADO DE TODAS LAS TRANSCCIONES CON ESTADO "PENDIG" PARA LAS CUALES HAN PASADO 7 MINUTES MINIMO:

1. LLAMAR AL METODO createAuth($wsdl, $login, $tranKey, $additional)
2. LLAMAR AL METODO verifyEstatusTransactions()
