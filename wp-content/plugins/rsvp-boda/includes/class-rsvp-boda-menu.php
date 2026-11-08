<?php
/**
 * Elección del menú del catering (pantalla Boda → Menú).
 *
 * La carta (catering Ntra. Sra. de Guía, Convento de la Luz) vive en catalogo(): son datos fijos
 * del documento que nos pasaron. Lo único que se guarda es la opción `rsvp_boda_menu` (autoload
 * desactivado): los votos de Belén y de Nai y la marca «Elegido» de cada plato, el menú base, el
 * número de invitados y los extras. Mismo control de versión optimista que Mesas.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class RSVP_Boda_Menu {

	const OPCION = 'rsvp_boda_menu';

	const MAX_INVITADOS   = 1000;
	const MAX_HORAS_EXTRA = 6;
	const MAX_MONTADITOS  = 5000;
	const MAX_LARGO_NOTAS = 2000;
	const INVITADOS_DEFECTO = 200;

	// Cata: como mucho 5 platos principales (primeros y segundos) para probar con el catering.
	const MAX_CATA       = 5;
	const SECCIONES_CATA = array( 'primeros', 'segundos' );

	public function __construct() {
		add_action( 'wp_ajax_rsvp_boda_menu_guardar', array( $this, 'ajax_guardar' ) );
	}

	/**
	 * Carta completa, en el orden en que se sirve.
	 *
	 * Sección: id, titulo, tipo (varios = tantos como se quiera; uno = exactamente uno;
	 * uno_opcional = uno o ninguno), ayuda y platos. Plato: id, nombre, menus (en qué menús
	 * cerrados viene), precio (€ por persona fuera del menú base, solo estaciones) y nota.
	 * Los ids no deben cambiar nunca: son las claves de lo guardado.
	 */
	public static function catalogo(): array {
		return array(
			'menus'     => array(
				array( 'id' => 1, 'nombre' => 'Menú 1', 'precio' => 98, 'resumen' => 'Ventresca de atún · carrillera · rejilla de chocolate y turrón. Estación de quesos.' ),
				array( 'id' => 2, 'nombre' => 'Menú 2', 'precio' => 103, 'resumen' => 'Gambas blancas · bacalao con trufa · bombón de solomillo. Estación marinera.' ),
				array( 'id' => 3, 'nombre' => 'Menú 3', 'precio' => 108, 'resumen' => 'Gambas blancas · merluza rellena · milhojas de presa. Estación de arroces.' ),
				array( 'id' => 4, 'nombre' => 'Menú 4', 'precio' => 113, 'resumen' => 'Gambas blancas extra · arroz caldoso con bogavante · solomillo con foie. Estación oriental.' ),
			),
			'barra'     => array(
				'precio'      => 14,
				'hora_extra'  => 3.5,
				'montaditos'  => array(
					array( 'id' => 'sencillos', 'nombre' => 'Montaditos de pringá, carne mechada o melva con morrón', 'precio' => 2 ),
					array( 'id' => 'especiales', 'nombre' => 'Montaditos de lomo al whisky o hamburguesa', 'precio' => 2.5 ),
				),
			),
			'secciones' => array(
				array(
					'id'     => 'frios',
					'titulo' => 'Aperitivos fríos',
					'tipo'   => 'varios',
					'ayuda'  => 'En el patio, durante hora y media. Los menús cerrados traen unos 15 aperitivos entre fríos y calientes.',
					'platos' => array(
						array( 'id' => 'f01', 'nombre' => 'Vasito de salmorejo de melocotón con virutas de salchichón ibérico y cebolla', 'menus' => array( 1 ), 'nota' => 'De temporada' ),
						array( 'id' => 'f02', 'nombre' => 'Vasito de salmorejo de mango con bastón de berenjena', 'menus' => array( 2 ) ),
						array( 'id' => 'f03', 'nombre' => 'Vasito de salmorejo de espárragos blancos con huevo de codorniz, vinagre balsámico y miel', 'menus' => array( 4 ) ),
						array( 'id' => 'f05', 'nombre' => 'Vasito de salmorejo de ciruela y ajoblanco', 'nota' => 'De temporada' ),
						array( 'id' => 'f04', 'nombre' => 'Esferificación de gazpacho con jamón cristalizado' ),
						array( 'id' => 'f12', 'nombre' => 'Crema fría de espárragos blancos con berberechos', 'menus' => array( 2 ) ),
						array( 'id' => 'f06', 'nombre' => 'Bolitas de crema de jamón con pistacho', 'menus' => array( 3 ) ),
						array( 'id' => 'f07', 'nombre' => 'Chupa chups de queso de cabra, uvas y crocanti de pistacho', 'menus' => array( 1 ) ),
						array( 'id' => 'f18', 'nombre' => 'Brocheta de gouda, tomate y comino con aceite de albahaca' ),
						array( 'id' => 'f30', 'nombre' => 'Torta de Inés Rosales con bola de queso crocanti' ),
						array( 'id' => 'f11', 'nombre' => 'Milhojas de berenjena y queso con miel y cebolla caramelizada', 'menus' => array( 1 ) ),
						array( 'id' => 'f37', 'nombre' => 'Milhojas de berenjena al dente con crema de queso y miel', 'menus' => array( 2, 4 ) ),
						array( 'id' => 'f13', 'nombre' => 'Hummus de remolacha en hoja de endivia', 'menus' => array( 3 ) ),
						array( 'id' => 'f25', 'nombre' => 'Endivias con queso, nueces y anchoas del Cantábrico con vinagre reducido', 'menus' => array( 4 ) ),
						array( 'id' => 'f14', 'nombre' => 'Cucharilla de aguacate y anchoas del Cantábrico', 'menus' => array( 4 ) ),
						array( 'id' => 'f10', 'nombre' => 'Tosta con bombón de salmón y anchoas del Cantábrico', 'menus' => array( 4 ) ),
						array( 'id' => 'f08', 'nombre' => 'Ceviche de salmón con base de lima', 'menus' => array( 2 ) ),
						array( 'id' => 'f19', 'nombre' => 'Dado de salmón con queso crema y huevas de lumpo', 'menus' => array( 2 ) ),
						array( 'id' => 'f36', 'nombre' => 'Dado de salmón con cebollita en vinagre', 'menus' => array( 1 ) ),
						array( 'id' => 'f21', 'nombre' => 'Canelón de salmón marinado con guacamole', 'menus' => array( 1 ) ),
						array( 'id' => 'f09', 'nombre' => 'Brandada de bacalao, pan rústico y aceite de perejil' ),
						array( 'id' => 'f22', 'nombre' => 'Macarons rellenos de crema de queso y ahumados (o de foie)', 'menus' => array( 2 ) ),
						array( 'id' => 'f17', 'nombre' => 'Mojama de Ayamonte con aceite de oliva y almendra laminada', 'menus' => array( 3 ) ),
						array( 'id' => 'f23', 'nombre' => 'Tosta de pimientos asados con ventresca de atún y crema de almendra frita', 'menus' => array( 3 ) ),
						array( 'id' => 'f24', 'nombre' => 'Tataki de atún con sésamo y mayonesa de wasabi y soja' ),
						array( 'id' => 'f16', 'nombre' => 'Taquito de pulpo con puré y pimentón', 'menus' => array( 2 ) ),
						array( 'id' => 'f28', 'nombre' => 'Emulsión templada de patata con pulpo a la brasa' ),
						array( 'id' => 'f26', 'nombre' => 'Brocheta de choquito de Huelva asado sobre alioli y aceite de perejil', 'menus' => array( 1 ) ),
						array( 'id' => 'f15', 'nombre' => 'Crepe con ensaladilla de gambas' ),
						array( 'id' => 'f33', 'nombre' => 'Tortitas de gambas al ajillo con aguacate' ),
						array( 'id' => 'f35', 'nombre' => 'Twister de langostinos en vaso ancho con salsa de anchoas', 'menus' => array( 2 ) ),
						array( 'id' => 'f27', 'nombre' => 'Cilindro de carpaccio de presa ibérica relleno de quesos y cebolla roja', 'menus' => array( 2 ) ),
						array( 'id' => 'f29', 'nombre' => 'Tosta con tartar de salchichón', 'menus' => array( 1 ) ),
						array( 'id' => 'f32', 'nombre' => 'Tosta de solomillo con queso de cabra y cebolla caramelizada' ),
						array( 'id' => 'f34', 'nombre' => 'Pan de especias con magret de pato y cereza', 'menus' => array( 2 ) ),
						array( 'id' => 'f20', 'nombre' => 'Piruleta de foie, chocolate y cristales comestibles', 'menus' => array( 3 ) ),
						array( 'id' => 'f31', 'nombre' => 'Turrón de foie, cebolla frita y mango' ),
					),
				),
				array(
					'id'     => 'calientes',
					'titulo' => 'Aperitivos calientes',
					'tipo'   => 'varios',
					'ayuda'  => 'También en el patio, junto con los fríos.',
					'platos' => array(
						array( 'id' => 'c11', 'nombre' => 'Croquetas caseras de boletus', 'menus' => array( 1 ) ),
						array( 'id' => 'c12', 'nombre' => 'Croquetas caseras de arroz negro', 'menus' => array( 2 ) ),
						array( 'id' => 'c13', 'nombre' => 'Croquetas caseras de espinacas, piñones y morcilla', 'menus' => array( 3 ) ),
						array( 'id' => 'c14', 'nombre' => 'Croquetas de gambas al ajillo y perejil', 'menus' => array( 4 ) ),
						array( 'id' => 'c15', 'nombre' => 'Croquetas caseras de chipirones y alioli', 'menus' => array( 1 ) ),
						array( 'id' => 'c05', 'nombre' => 'Cartucho de pescaíto frito', 'menus' => array( 3 ) ),
						array( 'id' => 'c10', 'nombre' => 'Taquito de rape frito', 'menus' => array( 4 ) ),
						array( 'id' => 'c23', 'nombre' => 'Sepia empanada con albahaca y alioli', 'menus' => array( 2, 3, 4 ) ),
						array( 'id' => 'c22', 'nombre' => 'Tortillitas de bacalao con miel de caña', 'menus' => array( 1 ) ),
						array( 'id' => 'c30', 'nombre' => 'Atún crujiente macerado en soja y jengibre', 'menus' => array( 4 ) ),
						array( 'id' => 'c31', 'nombre' => 'Crujiente de salmón con salsa teriyaki', 'menus' => array( 1 ) ),
						array( 'id' => 'c16', 'nombre' => 'Brocheta crujiente de gambón con salsa de mango', 'menus' => array( 2 ) ),
						array( 'id' => 'c27', 'nombre' => 'Brocheta de langostino en tempura con idiazábal y lechuga de mar', 'menus' => array( 3 ) ),
						array( 'id' => 'c29', 'nombre' => 'Mini croissant de langostino y alioli', 'menus' => array( 4 ) ),
						array( 'id' => 'c03', 'nombre' => 'Brocheta de rape y calabacín con alioli', 'menus' => array( 4 ) ),
						array( 'id' => 'c20', 'nombre' => 'Brocheta de rape y langostinos con calabacín', 'menus' => array( 3 ), 'nota' => 'En el Menú 3, con emulsión de escalivada' ),
						array( 'id' => 'c01', 'nombre' => 'Mini brocheta de solomillo con cebolla, pimiento y mojo picón', 'menus' => array( 1 ) ),
						array( 'id' => 'c04', 'nombre' => 'Brocheta de ave con mostaza y polvo de ibérico', 'menus' => array( 4 ) ),
						array( 'id' => 'c06', 'nombre' => 'Brocheta de magret de pato y cereza' ),
						array( 'id' => 'c07', 'nombre' => 'Chupa chups de muslo de codorniz frito con salsa tártara', 'menus' => array( 3 ) ),
						array( 'id' => 'c02', 'nombre' => 'Mini hamburguesa de ternera con queso y huevo de codorniz', 'menus' => array( 2 ) ),
						array( 'id' => 'c17', 'nombre' => 'Canastilla de carrillera al Pedro Ximénez', 'menus' => array( 3 ) ),
						array( 'id' => 'c28', 'nombre' => 'Cucharilla de pluma ibérica, cuscús y soja', 'menus' => array( 1 ) ),
						array( 'id' => 'c25', 'nombre' => 'Bollitos preñaos de chorizo ibérico', 'menus' => array( 1, 4 ) ),
						array( 'id' => 'c24', 'nombre' => 'Bocado de pasta brick con morcilla y manzana reineta', 'menus' => array( 3 ) ),
						array( 'id' => 'c26', 'nombre' => 'Mini cazuela de migas con pimientos y huevo de codorniz', 'menus' => array( 3 ) ),
						array( 'id' => 'c18', 'nombre' => 'Tartaleta de pisto y huevo de codorniz con cristales de jamón', 'menus' => array( 1 ) ),
						array( 'id' => 'c19', 'nombre' => 'Huevos de codorniz a la Villeroy', 'menus' => array( 2 ) ),
						array( 'id' => 'c33', 'nombre' => 'Nido de huevo de codorniz con cristal de ibérico' ),
						array( 'id' => 'c21', 'nombre' => 'Miniquiche de beicon y setas de temporada', 'menus' => array( 4 ) ),
						array( 'id' => 'c32', 'nombre' => 'Saquito relleno de queso y pimiento confitado' ),
						array( 'id' => 'c34', 'nombre' => 'Champiñones rebozados en harina de garbanzo con salsa de cabrales', 'menus' => array( 4 ) ),
						array( 'id' => 'c08', 'nombre' => 'Mini pizza casera' ),
						array( 'id' => 'c09', 'nombre' => 'Mini fajita mexicana' ),
					),
				),
				array(
					'id'     => 'estaciones',
					'titulo' => 'Estaciones',
					'tipo'   => 'varios',
					'ayuda'  => 'Puestos durante el aperitivo. Todos los menús traen las dos primeras y una estación especial; el resto suman su precio por persona.',
					'platos' => array(
						array( 'id' => 'e01', 'nombre' => 'Vinos y botellines helados en calderas de zinc, con cartucho de patatas', 'menus' => array( 1, 2, 3, 4 ) ),
						array( 'id' => 'e02', 'nombre' => 'Jamón ibérico de bellota cortado en directo', 'menus' => array( 1, 2, 3, 4 ), 'nota' => 'Fuera de menú: 350 € el jamón + 100 € el cortador' ),
						array( 'id' => 'e03', 'nombre' => 'Quesos artesanos (más de 10 tipos) con frutos secos, uvas, regañás y mermeladas caseras', 'menus' => array( 1 ), 'precio' => 3 ),
						array( 'id' => 'e04', 'nombre' => 'Marinera: coquinas, almejas y chirlas en directo con distintas salsas', 'menus' => array( 2 ), 'precio' => 3 ),
						array( 'id' => 'e05', 'nombre' => 'Arroces en directo: rape y gambas, negro con alioli, presa ibérica y setas con verduras', 'menus' => array( 3 ), 'precio' => 3 ),
						array( 'id' => 'e06', 'nombre' => 'Oriental: sushi de verduras, pescado y tataki con sus salsas', 'menus' => array( 4 ), 'precio' => 3 ),
						array( 'id' => 'e09', 'nombre' => 'Tortillitas de camarones en directo', 'precio' => 3 ),
						array( 'id' => 'e10', 'nombre' => 'Pulpo en directo', 'precio' => 4 ),
						array( 'id' => 'e08', 'nombre' => 'Parrillada de ibéricos y verduras en directo (presa, pluma, abanico y lomo)', 'precio' => 4 ),
						array( 'id' => 'e07', 'nombre' => 'Chupitos de frutas', 'precio' => 1.5 ),
					),
				),
				array(
					'id'     => 'individual',
					'titulo' => 'Plato individual',
					'tipo'   => 'uno_opcional',
					'ayuda'  => 'Ya en la mesa, antes del primero. Uno o ninguno (el Menú 1 no lo trae).',
					'platos' => array(
						array( 'id' => 'i01', 'nombre' => 'Gambas blancas de la costa de Huelva', 'menus' => array( 2, 3 ) ),
						array( 'id' => 'i02', 'nombre' => 'Gambas blancas de Huelva extra', 'menus' => array( 4 ) ),
					),
				),
				array(
					'id'     => 'primeros',
					'titulo' => 'Primer plato',
					'tipo'   => 'uno',
					'ayuda'  => 'Se elige uno.',
					'platos' => array(
						array( 'id' => 'p01', 'nombre' => 'Ceviche de gambas macerado en limón' ),
						array( 'id' => 'p11', 'nombre' => 'Tartar de atún con aguacate y ajoblanco de almendra' ),
						array( 'id' => 'p03', 'nombre' => 'Ventresca de atún sobre demicuit de almendra con polvo de volutas deshidratado', 'menus' => array( 1 ) ),
						array( 'id' => 'p08', 'nombre' => 'Lomo de atún con estofado de costilla de bellota, trompetas de la muerte y gazpacho solidificado' ),
						array( 'id' => 'p04', 'nombre' => 'Ensalada de guacamole con carabinero, aceite de molusco y espuma de limón' ),
						array( 'id' => 'p05', 'nombre' => 'Ensalada de carabinero al dente con tartar de manzana y mango y brotes verdes' ),
						array( 'id' => 'p06', 'nombre' => 'Ensalada de bogavante sobre guacamole, brotes tiernos y vinagreta de miel' ),
						array( 'id' => 'p07', 'nombre' => 'Bacalao con esencia de trufa blanca de Paymogo, hierbabuena y esponja de pera', 'menus' => array( 2 ) ),
						array( 'id' => 'p10', 'nombre' => 'Merluza rellena de bechamel de bacalao y gambas con salsa marinera, puerro frito y verduritas', 'menus' => array( 3 ) ),
						array( 'id' => 'p02', 'nombre' => 'Corvina a baja temperatura sobre choco crujiente de Punta Umbría, trufa negra y aceite de Teruel' ),
						array( 'id' => 'p09', 'nombre' => 'Lomo de dorada marinado en vino y membrillo, con su esencia y flor seca' ),
						array( 'id' => 'p15', 'nombre' => 'Rodaballo sobre arroz salvaje y crema de berberechos' ),
						array( 'id' => 'p12', 'nombre' => 'Risotto de boletus con peineta de queso viejo cristalizado' ),
						array( 'id' => 'p13', 'nombre' => 'Arroz meloso con carabinero' ),
						array( 'id' => 'p14', 'nombre' => 'Arroz meloso con bogavante' ),
						array( 'id' => 'p16', 'nombre' => 'Arroz caldoso con bogavante', 'menus' => array( 4 ) ),
					),
				),
				array(
					'id'     => 'sorbetes',
					'titulo' => 'Pausa fría',
					'tipo'   => 'uno',
					'ayuda'  => 'Sorbete entre el primero y el segundo. Se elige uno.',
					'platos' => array(
						array( 'id' => 's01', 'nombre' => 'Sorbete de limón al cava' ),
						array( 'id' => 's02', 'nombre' => 'Sorbete de mandarina al Grand Marnier' ),
						array( 'id' => 's03', 'nombre' => 'Sorbete de mango y cítricos', 'menus' => array( 1 ) ),
						array( 'id' => 's04', 'nombre' => 'Sorbete de gin-tonic', 'menus' => array( 2 ) ),
						array( 'id' => 's05', 'nombre' => 'Sorbete de mojito con hierbabuena', 'menus' => array( 3 ) ),
						array( 'id' => 's06', 'nombre' => 'Daiquiri de melón', 'menus' => array( 4 ) ),
						array( 'id' => 's07', 'nombre' => 'Sorbete de piña colada' ),
					),
				),
				array(
					'id'     => 'segundos',
					'titulo' => 'Segundo plato',
					'tipo'   => 'uno',
					'ayuda'  => 'Se elige uno.',
					'platos' => array(
						array( 'id' => 'g01', 'nombre' => 'Bombón de solomillo a baja temperatura relleno de paté, con salsa de pimienta', 'menus' => array( 2 ) ),
						array( 'id' => 'g08', 'nombre' => 'Chof de solomillo de ternera con foie de pato, reducción de Pedro Ximénez y setas salteadas', 'menus' => array( 4 ) ),
						array( 'id' => 'g09', 'nombre' => 'Solomillo ibérico Wellington con patatas salteadas y espárrago verde' ),
						array( 'id' => 'g05', 'nombre' => 'Timbal de carrillera de ternera con crema de calabaza, semillas y espárragos verdes', 'menus' => array( 1 ) ),
						array( 'id' => 'g06', 'nombre' => 'Milhojas de presa ibérica con jamón, salsa de piñones, puré de patata y zanahoria y espárragos', 'menus' => array( 3 ) ),
						array( 'id' => 'g02', 'nombre' => 'Presa ibérica al estilo asiático con gominolas de uva zalema, vainilla y patatas baby' ),
						array( 'id' => 'g03', 'nombre' => 'Brazuelo de cordero deshuesado con tarta de melón, menta y sopa fría de ajoblanco' ),
						array( 'id' => 'g04', 'nombre' => 'Cordero lechal a 64 °C con patatas ratte, cúrcuma y glaseado en su jugo' ),
						array( 'id' => 'g07', 'nombre' => 'Magret de pato salvaje sobre naranja caramelizada y salsa de frutos rojos' ),
					),
				),
				array(
					'id'     => 'postres',
					'titulo' => 'Postre',
					'tipo'   => 'uno',
					'ayuda'  => 'Se elige uno. Después, café.',
					'platos' => array(
						array( 'id' => 'd06', 'nombre' => 'Rejilla de chocolate rellena de mousse de turrón con coulis de Baileys', 'menus' => array( 1 ), 'nota' => 'En el Menú 1, con crema de pistacho' ),
						array( 'id' => 'd05', 'nombre' => 'Rejilla de chocolate con bavarois de pistacho y coulis de melocotón' ),
						array( 'id' => 'd16', 'nombre' => 'Marquesa de chocolate, cumbre de café y helado de limón', 'menus' => array( 2 ) ),
						array( 'id' => 'd15', 'nombre' => 'Torrija de brioche caramelizada con helado de turrón de Jijona', 'menus' => array( 3 ) ),
						array( 'id' => 'd13', 'nombre' => 'Sopa de chocolate blanco con helado de frutos rojos, teja de almendra y brownie', 'menus' => array( 4 ) ),
						array( 'id' => 'd14', 'nombre' => 'Tarta de queso con coulis de vainilla y teja de almendra' ),
						array( 'id' => 'd02', 'nombre' => 'Tiramisú italiano con crema de café y delicias heladas' ),
						array( 'id' => 'd12', 'nombre' => 'Brownie de chocolate y nuez con tarta San Marcos y bolas de melón' ),
						array( 'id' => 'd07', 'nombre' => 'Selva negra con coulis de vainilla' ),
						array( 'id' => 'd03', 'nombre' => 'Tulipa de canela con helado y barquillo' ),
						array( 'id' => 'd01', 'nombre' => 'Mousse de crema catalana con selva negra y coulis de naranja' ),
						array( 'id' => 'd04', 'nombre' => 'Mousse de Guadalquivir con crema de café' ),
						array( 'id' => 'd08', 'nombre' => 'Mousse de caramelo con coulis de toffee' ),
						array( 'id' => 'd09', 'nombre' => 'Mousse de stracciatella con coulis de caramelo' ),
						array( 'id' => 'd10', 'nombre' => 'Mousse de dos chocolates con coulis de pistacho' ),
						array( 'id' => 'd11', 'nombre' => 'Mousse de naranja con sirope de caramelo y láminas de almendra' ),
						array( 'id' => 'd17', 'nombre' => 'Espolea con daditos y canela', 'nota' => 'Así viene en la carta; preguntar al catering qué es' ),
					),
				),
				array(
					'id'     => 'barra',
					'titulo' => 'Barra libre y extras',
					'tipo'   => 'varios',
					'ayuda'  => 'Los precios de los menús no incluyen la barra libre.',
					'platos' => array(
						array( 'id' => 'x01', 'nombre' => 'Barra libre de 4 horas (vaso ancho y copa de balón)', 'precio' => 14, 'nota' => 'White Label, JB, Johnnie Walker Red, Seagram\'s, Beefeater, Puerto de Indias, Barceló, Legendario, Cacique, Malibú, vodka y licores sin alcohol' ),
						array( 'id' => 'x02', 'nombre' => 'Barra de mojitos y caipiriñas', 'precio' => 3 ),
					),
				),
			),
		);
	}

	/**
	 * Ids de plato => id de su sección.
	 */
	private static function ids_platos(): array {
		$ids = array();
		foreach ( self::catalogo()['secciones'] as $seccion ) {
			foreach ( $seccion['platos'] as $plato ) {
				$ids[ $plato['id'] ] = $seccion['id'];
			}
		}
		return $ids;
	}

	public static function estado_vacio(): array {
		return array(
			'version'    => 0,
			'base'       => 0,
			'invitados'  => self::INVITADOS_DEFECTO,
			'horas'      => 0,
			'montaditos' => array(
				'sencillos'  => 0,
				'especiales' => 0,
			),
			'platos'     => array(),
			'notas'      => '',
		);
	}

	public static function obtener(): array {
		$guardado = get_option( self::OPCION, '' );
		if ( ! is_array( $guardado ) ) {
			$guardado = array();
		}
		$estado            = self::sanear_estado( $guardado );
		$estado['version'] = isset( $guardado['version'] ) ? absint( $guardado['version'] ) : 0;
		return $estado;
	}

	/**
	 * Sanea un estado recibido (del navegador o de la opción). No toca la versión.
	 * Cada plato guardado: b (voto de Belén), n (voto de Nai), e (elegido); solo se guardan los
	 * que tienen algo marcado. En las secciones de «uno» se deja como mucho un elegido.
	 */
	public static function sanear_estado( array $entrada ): array {
		$estado = self::estado_vacio();

		$base           = isset( $entrada['base'] ) ? absint( $entrada['base'] ) : 0;
		$estado['base'] = $base <= 4 ? $base : 0;

		$invitados           = isset( $entrada['invitados'] ) ? absint( $entrada['invitados'] ) : self::INVITADOS_DEFECTO;
		$estado['invitados'] = max( 1, min( self::MAX_INVITADOS, $invitados ) );

		$estado['horas'] = min( self::MAX_HORAS_EXTRA, isset( $entrada['horas'] ) ? absint( $entrada['horas'] ) : 0 );

		foreach ( array_keys( $estado['montaditos'] ) as $tipo ) {
			$valor                          = $entrada['montaditos'][ $tipo ] ?? 0;
			$estado['montaditos'][ $tipo ] = min( self::MAX_MONTADITOS, is_scalar( $valor ) ? absint( $valor ) : 0 );
		}

		$tipos_seccion = array_column( self::catalogo()['secciones'], 'tipo', 'id' );
		$ids           = self::ids_platos();
		$con_elegido   = array();
		$en_cata       = 0;
		$platos        = $entrada['platos'] ?? array();

		if ( is_array( $platos ) ) {
			foreach ( $platos as $id => $marcas ) {
				$id = (string) $id;
				if ( ! isset( $ids[ $id ] ) || ! is_array( $marcas ) ) {
					continue;
				}
				$limpio = array(
					'b' => ! empty( $marcas['b'] ),
					'n' => ! empty( $marcas['n'] ),
					'e' => ! empty( $marcas['e'] ),
					'c' => false,
				);
				$seccion = $ids[ $id ];
				// Cata: solo primeros y segundos, y como mucho MAX_CATA.
				if ( ! empty( $marcas['c'] ) && in_array( $seccion, self::SECCIONES_CATA, true ) && $en_cata < self::MAX_CATA ) {
					$limpio['c'] = true;
					$en_cata++;
				}
				if ( $limpio['e'] && 'varios' !== $tipos_seccion[ $seccion ] ) {
					if ( isset( $con_elegido[ $seccion ] ) ) {
						$limpio['e'] = false;
					}
					$con_elegido[ $seccion ] = true;
				}
				if ( $limpio['b'] || $limpio['n'] || $limpio['e'] || $limpio['c'] ) {
					$estado['platos'][ $id ] = $limpio;
				}
			}
		}

		$notas           = $entrada['notas'] ?? '';
		$notas           = is_scalar( $notas ) ? sanitize_textarea_field( (string) $notas ) : '';
		$estado['notas'] = function_exists( 'mb_substr' ) ? mb_substr( $notas, 0, self::MAX_LARGO_NOTAS ) : substr( $notas, 0, self::MAX_LARGO_NOTAS );

		return $estado;
	}

	/**
	 * AJAX: guarda el estado completo, con el mismo control de versión que Mesas.
	 */
	public function ajax_guardar(): void {
		if ( ! check_ajax_referer( 'rsvp_boda_menu', 'nonce', false ) ) {
			wp_send_json_error(
				array(
					'codigo'  => 'nonce',
					'mensaje' => 'La sesión ha caducado. Recarga la página (tus cambios no guardados se perderán).',
				),
				403
			);
		}
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error(
				array(
					'codigo'  => 'permiso',
					'mensaje' => 'No tienes permiso para hacer esto.',
				),
				403
			);
		}

		$entrada = json_decode( wp_unslash( (string) ( $_POST['estado'] ?? '' ) ), true );
		if ( ! is_array( $entrada ) ) {
			wp_send_json_error(
				array(
					'codigo'  => 'datos',
					'mensaje' => 'No se han recibido datos válidos. Inténtalo de nuevo.',
				),
				400
			);
		}

		$guardado = get_option( self::OPCION, '' );
		$version  = is_array( $guardado ) && isset( $guardado['version'] ) ? absint( $guardado['version'] ) : 0;

		if ( ! isset( $_POST['version'] ) || absint( wp_unslash( $_POST['version'] ) ) !== $version ) {
			wp_send_json_error(
				array(
					'codigo'  => 'conflicto',
					'mensaje' => 'La otra persona ha cambiado el menú mientras editabas. Recarga la página (tus cambios no guardados se perderán).',
				),
				409
			);
		}

		$estado            = self::sanear_estado( $entrada );
		$estado['version'] = $version + 1;

		if ( false === get_option( self::OPCION, false ) ) {
			add_option( self::OPCION, $estado, '', 'no' );
		} else {
			update_option( self::OPCION, $estado, false );
		}

		wp_send_json_success(
			array(
				'version' => $estado['version'],
				'mensaje' => 'Menú guardado.',
			)
		);
	}
}
