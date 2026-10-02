# Prompt para Claude Code

> Copia todo lo que hay debajo de esta línea y pégalo en Claude Code.
> Antes de enviarlo, rellena los datos entre corchetes.

---

## Contexto

Estoy montando la web de mi boda en **bodabelennai.es**, alojada en Hostinger con WordPress.
Trabajo habitualmente con WordPress, PHP y plantillas, así que puedes darme código directamente sin explicarme lo básico.

Datos de la boda (rellenar):
- Novios: Belén y [nombre]
- Fecha y hora: [fecha], ceremonia a las [hora]
- Lugar de la ceremonia: [nombre y dirección]
- Lugar del banquete: [nombre y dirección]
- Nº aproximado de invitados: [número]
- Fecha límite para confirmar: [fecha]
- Teléfono de contacto para dudas: [número]

## Qué quiero que construyas

**1. Un child theme** sobre un tema base ligero (Twenty Twenty-Four o Astra, elige tú y justifícalo).
Estructura completa: `style.css`, `functions.php`, `header.php`, `footer.php` y una plantilla de página
`page-boda.php` para la portada.

**2. Un plugin propio** llamado `rsvp-boda` que gestione las confirmaciones. No quiero depender del
formulario de un tercero, quiero control total del código y de los datos.

## Diseño

Estilo andaluz contemporáneo, sin caer en el tópico del abanico y la peineta.

- Paleta: albero (#D9A441 aprox.), añil (#1F3A6E aprox.), blanco cal, terracota como acento.
  Ajusta los tonos si encuentras una combinación mejor.
- Patrón de azulejo sevillano como fondo sutil en algunas secciones (SVG en línea, no imagen pesada).
- Tipografías: una con carácter de rotulación cerámica para nombres y titulares, una serif limpia y
  legible para el cuerpo. Usa Google Fonts pero cárgalas en local para no depender de terceros.
- Mobile-first de verdad: el 85% de los invitados van a abrirla desde el móvil por WhatsApp.
- Una sola página larga con navegación por anclas, no varias páginas.

## Secciones de la página

1. Portada con los nombres, la fecha y cuenta atrás.
2. Nuestra historia (texto e imágenes, editable desde el admin).
3. Ceremonia y banquete: hora, lugar, mapa embebido, cómo llegar y aparcamiento.
4. Bus / transporte: horarios y paradas.
5. Alojamiento recomendado.
6. Código de vestimenta.
7. Lista de regalos / número de cuenta (que se pueda ocultar con un ajuste).
8. Formulario de confirmación.
9. Contacto con el teléfono, para quien se atasque.

Todos los textos y datos deben ser editables desde una página de ajustes en el admin de WordPress.
No quiero tener que tocar el código para cambiar una hora.

## Formulario de confirmación (lo más importante)

Campos:
- Nombre y apellidos
- ¿Asistes? sí / no
- Número de acompañantes (solo visible si ha marcado que sí)
- Nombre de cada acompañante (campos que se generan según el número elegido)
- Alergias o intolerancias / menú especial (texto libre)
- Menú infantil: sí / no
- ¿Necesita bus? sí / no, con selección de parada
- Canción que le gustaría que sonara (opcional, campo divertido)
- Teléfono de contacto

Requisitos técnicos del RSVP, esto es lo crítico:

- **Cada envío se guarda en una tabla propia de la base de datos** (`wp_rsvp_boda`), creada con
  `dbDelta` al activar el plugin. El email es un aviso secundario, nunca el registro principal.
- Envío por AJAX con nonce, sanitización de todos los campos y validación tanto en cliente como en servidor.
- Si el guardado en base de datos falla por lo que sea, que quede registrado en un log y que el usuario
  vea un mensaje con el teléfono de contacto. Nunca un fallo silencioso.
- Mensaje de confirmación claro en pantalla al enviar.
- Que el invitado pueda volver a entrar con su enlace y modificar su respuesta, en lugar de crear un
  duplicado. Si aun así hay duplicados, que se detecten en el panel y no se pierda ninguno.
- Antispam con honeypot o Cloudflare Turnstile. **Nada de reCAPTCHA con imágenes**, la gente mayor
  abandona ahí.
- La página del formulario y las peticiones AJAX deben quedar excluidas de cualquier caché. Déjame
  documentado cómo hacerlo en LiteSpeed, que es lo que suele traer Hostinger.

## Panel de administración

Una pantalla en el admin con:

- Lista de confirmaciones en una `WP_List_Table`: nombre, asiste, nº de acompañantes, alergias, bus, fecha.
- Filtros por asiste sí/no y por bus sí/no. Buscador por nombre.
- Contadores arriba del todo: total confirmados, total que no vienen, pendientes de responder,
  nº de menús con alergias, nº de plazas de bus por parada.
- **Exportación a CSV** con un botón, con codificación UTF-8 con BOM para que Excel no destroce las tildes.
- Lista de invitados esperados (que yo pueda cargar) para poder ver quién falta por contestar.

## Envío por WhatsApp

El enlace de la invitación se reparte por WhatsApp, así que:

- **Etiquetas Open Graph** en el `<head>`: `og:title`, `og:description`, `og:image`, `og:url`, `og:type`.
  Imagen de 1200x630 px por debajo de 300 KB. Añade también las etiquetas de Twitter Card.
- **Enlaces personalizados por invitado**: un parámetro en la URL tipo `?i=carmen-y-jose` que se lea
  en la plantilla para saludar por su nombre y precargar el campo del formulario. Sanitizado, obviamente.
- Un generador en el admin que, a partir de la lista de invitados, me produzca los enlaces personalizados
  ya listos para copiar y pegar en WhatsApp uno a uno.
- **Aviso a mi móvil por WhatsApp cada vez que alguien confirme**, mediante webhook a CallMeBot o
  Twilio. Que sea configurable desde los ajustes y que si falla no afecte al guardado de la confirmación.
  Explícame qué hace falta dar de alta en cada servicio.

## Email

- Correo automático de confirmación al invitado, con el resumen de su respuesta y los datos del evento.
- Aviso a nuestro correo.
- Configúralo para usar SMTP externo (Brevo o Mailgun) en vez de la función `mail()` de PHP.
  Dime qué registros SPF y DKIM tengo que añadir en el DNS.

## Requisitos generales

- PHP 8.1+, código limpio, con prefijos en funciones y sin conflictos de nombres.
- Nada de dependencias raras ni plugins de pago.
- Accesible: contraste suficiente, etiquetas en los campos, navegable con teclado.
- Que cargue rápido en 4G con cobertura mediocre.
- Comenta el código en español.

## Cómo quiero que trabajes

1. Empieza proponiéndome la estructura de archivos y espera mi visto bueno antes de escribir todo.
2. Luego ve por partes: primero el plugin del RSVP con su tabla y su panel, después el child theme y el diseño.
3. Al final, dame una **checklist de pruebas** antes de repartir el enlace: confirmar desde Gmail,
   Hotmail e iCloud, comprobar que la entrada aparece en el panel, verificar la vista previa del enlace
   en WhatsApp, y probar el formulario desde un móvil real.
