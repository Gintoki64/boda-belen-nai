# Web de boda — bodabelennai.es

Web de la boda de Belén y Nai, sobre WordPress + Hostinger. Dos entregables propios:
child theme `astra-child-belen-nai` y plugin `rsvp-boda`. El encargo original completo
está en `prompt-claude-code-boda.md`; el plan de diseño/arquitectura detallado está en
`C:\Users\knigh\.claude\plans\c-users-knigh-desktop-proyectos-boda-bel-snappy-muffin.md`.

## Decisiones ya tomadas (no volver a preguntar)

- **Tema base: Astra** (no Twenty Twenty-Four) — es clásico (no FSE), usa header.php/footer.php
  como pide el encargo. Astra tiene que estar **instalado** en el servidor (aunque no activo)
  para que nuestro child theme funcione, porque su `style.css` declara `Template: astra`.
- **SMTP: Brevo** (elegido sobre Mailgun).
- **Antispam: solo honeypot**, sin Cloudflare Turnstile.
- **Sin aviso de WhatsApp al móvil** (CallMeBot/Twilio) al confirmar — el usuario dijo explícitamente
  que no le hace falta. No construir esto si se vuelve a mencionar el prompt original.
- **Permiso de acompañante (+1) por invitado**, no global: cada fila de `wp_rsvp_boda_invitados`
  tiene `permite_acompanantes`. El generador de enlaces los agrupa en dos bloques para pegar en
  WhatsApp ("Con +1" / "Sin +1"). Hay un ajuste global aparte solo para el caso raro de acceso
  sin enlace personalizado.
- **Se añadió un campo `email` opcional** al formulario (no estaba en la lista de campos del
  prompt original) porque el prompt sí pedía un correo de confirmación al invitado, y sin email
  no se podía enviar. Columna `email` en `wp_rsvp_boda`.
- **Tipografías cambiadas a Adobe Fonts (Typekit) el 2026-09-07**, sustituyendo a las Google Fonts
  autoalojadas del encargo original — decisión de Belén, que tiene cuenta de pago con esas fuentes
  ya compradas. Esto contradice a propósito el "cárgalas en local para no depender de terceros"
  del prompt original: Adobe no permite auto-alojar los archivos de fuente por licencia, así que
  la dependencia externa es inevitable si se usan estas fuentes. Ver detalle en la sección de
  cambios del 2026-09-07 más abajo.

## Arquitectura

- Todo el contenido editable (textos, fechas, IBAN, etc.) vive en el plugin, en la opción
  `rsvp_boda_settings` (Settings API), nunca en el theme ni en el Customizer.
- La base de datos (`wp_rsvp_boda`, `wp_rsvp_boda_invitados`) es la fuente de verdad; el email
  es siempre un aviso de mejor esfuerzo (nunca bloquea ni condiciona el guardado).
- Enlaces personalizados `?i=slug`: resuelven el invitado, precargan el formulario si ya
  respondió, y el permiso de acompañante se recalcula SIEMPRE en servidor a partir del slug
  (nunca se confía en el cliente).
- Dedup: upsert atómico por `guest_slug` (clave única) cuando hay enlace; cookie firmada
  (`rsvp_boda_edicion`) cuando no hay enlace; el panel marca por `nombre_normalizado` repetido
  como red de seguridad final.
- El `<form>` del RSVP lo pinta el plugin (`rsvp_boda_render_form()`), el theme solo lo invoca
  desde `template-parts/section-rsvp.php` — acoplamiento mínimo a propósito.

## Estado del despliegue (ir actualizando esta sección)

- **WordPress instalado y funcionando** en Hostinger sobre `bodabelennai.es`.
- Astra instalado, child theme `astra-child-belen-nai` activado, plugin `rsvp-boda` activado.
- Página "Boda" creada con la plantilla "Página de la boda", publicada en `/boda/` — **confirmado
  funcionando** (verificado visitando la URL: nav, cuenta atrás, ceremonia/banquete, formulario RSVP).
- **Página "Boda" fijada como portada estática** en Ajustes → Lectura — hecho.
- Pendiente: rellenar datos reales en **Boda → Ajustes generales** (menú propio del plugin,
  icono de corazón en la barra lateral de wp-admin).
- **Formulario RSVP funcionando en producción** (2026-10-02): el usuario lo probó tras subir los
  arreglos, y verificado con `curl`: la portada ya sale con `Cache-Control: no-store` /
  `X-LiteSpeed-Cache-Control: no-cache`, el JS del plugin con versión por `filemtime` y sin rastro
  del azulejo. Pendiente aún: comprobar en Boda → Confirmaciones que la respuesta de prueba se ve bien.
- **Repositorio en GitHub**: https://github.com/Gintoki64/boda-belen-nai (público, rama `main`).
  `.gitignore` excluye `cxonversacion.jpg` (captura privada de WhatsApp), `fondoPaginaBoda.jpg`
  original, `*.zip`, `public_html/` y `wp-config.php`. Ojo: es público — nunca commitear
  credenciales ni datos de invitados (CSV de importación, exportaciones). GitHub es solo copia del
  código; el despliegue real sigue siendo por FTP (FileZilla, sitio guardado como
  **"Boda Belen y nai"**, sin tilde — `filezilla.exe -c "0/Boda Belen y nai"`).
- Recomendado (no confirmado que se haya hecho): asignar la plantilla "Página de la boda" a la
  página "Boda" en el editor (Atributos de página → Plantilla); hoy funciona solo por el slug.
- Pendiente: configurar Brevo (SMTP + SPF/DKIM) y comprobar que llegan los correos.
- Pendiente: configurar exclusión de caché en LiteSpeed si Hostinger lo activa (ver plan) — causó
  el problema real de "guardo ajustes y no se ve en otros dispositivos" (2026-09-06); solución
  inmediata fue purgar caché desde hPanel, esto sigue pendiente de configurar de forma permanente.
- **Correcciones del 2026-09-06 ya subidas y confirmadas en producción** (2026-09-07, verificado
  con `curl` directo contra `bodabelennai.es` sin pasar por caché: `main.css` tiene el fondo floral
  y `Last-Modified` de esa fecha). `wp-content/` y `public_html/wp-content/` de este repo local
  vuelven a coincidir con el servidor real.
- **Acceso FTP configurado** (2026-09-07): FileZilla instalado, sitio guardado como "Boda Belen y
  nai" con la IP de Hostinger (no `ftp.bodabelennai.es`, la web pidió la IP directa). Usar esto
  para futuros despliegues en vez de zips + uploader del admin de WordPress — mucho más fiable.
- **La carpeta `public_html/` local desapareció** en algún momento tras configurar el FTP (ya no
  hacía falta como copia de inspección, con FTP se navega el servidor real directamente) — dejar
  de sincronizar ahí; el repo local de trabajo es solo `wp-content/`, y el despliegue real se hace
  por FTP directamente a `public_html/wp-content/...` en el servidor.

## Correcciones aplicadas 2026-09-06 (revisión con dos subagentes, pendientes de subir)

Theme `astra-child-belen-nai`:
- `functions.php` encolaba `astra/style.css`, que va vacío (Astra carga su CSS real por otra vía)
  — quitado, y las hojas propias ya no dependen de ese handle muerto.
- Versión de assets ahora se calcula por `filemtime()` (`boda_theme_asset_version()`) en vez de
  una constante fija `1.0.0`, para que un cambio en CSS/JS fuerce cache-busting real.
- El menú ("Nuestra historia", "Alojamiento", "Vestimenta") mostraba enlaces a secciones que no se
  llegan a pintar si el campo del admin está vacío (ancla rota) — ahora `header.php` y
  `page-boda.php` comparten las mismas comprobaciones que las template-parts
  (`boda_theme_hay_historia()`, `boda_theme_hay_alojamiento()`, `boda_theme_hay_vestimenta()` en
  `functions.php`), igual que ya se hacía con bus/regalo.
- Menú móvil: los enlaces fuera de pantalla eran alcanzables por teclado/lector de pantalla aunque
  el menú estuviera "cerrado" — ahora se marcan `inert` según el estado y el breakpoint
  (`assets/js/nav-anchors.js`). El texto accesible del botón también alterna "Abrir/Cerrar menú".
- El panel del menú móvil asumía una cabecera de 64px fijos (`top: 64px`) — ahora se mide la
  altura real de `.boda-cabecera` por JS y se guarda en `--boda-alto-cabecera` (con `64px` de
  fallback si no hay JS).
- **Fondo floral añadido**: `fondoPaginaBoda.jpg` (subido por el usuario a la raíz del proyecto)
  comprimido de 242KB a 40KB (560×396) y guardado como
  `assets/images/fondo-boda-pattern.jpg`; se aplica como fondo de `body` repetido, con un velo
  semitransparente del "blanco cal" encima (misma idea que el azulejo: siempre detrás, opacidad
  baja) en `assets/css/main.css`. Valores elegidos por el usuario tras probar una vista previa
  interactiva (artifact): velo al **60% de opacidad**, patrón a **600px**.

Plugin `rsvp-boda`:
- `class-rsvp-boda-ajax.php` (`procesar_acompanantes`): off-by-one que dejaba colarse 1
  acompañante cuando `limite_acompanantes` = 0 — el corte ahora se comprueba antes de añadir, no
  después.
- `class-rsvp-boda-invitados.php` (importación CSV): un CSV con BOM UTF-8 (típico de Excel/Sheets
  en Windows) hacía que la fila de cabecera "nombre" no se reconociera como tal y se importara
  como invitado — se descarta el BOM al abrir el archivo.
- `class-rsvp-boda-ajax.php` (`enviar_correos`): un fallo de envío ahora sí se registra con
  `RSVP_Boda_Logger::error()` (antes solo quedaba en una columna de la BD que nadie veía), y
  `email_error` se limpia si un reenvío posterior sí funciona.
- Nueva columna **"Email"** en Boda → Confirmaciones (`class-rsvp-boda-list-table.php`) que muestra
  "Enviado" o "Fallo de envío" (con el motivo en el `title`) por fila — antes no había forma de
  detectar desde el panel que Brevo hubiera dejado de funcionar.
- `class-rsvp-boda-admin.php`: los `require` de las vistas del admin pasaron a `require_once`
  (las vistas declaran funciones globales; con `require` a secas un doble include accidental
  habría sido un fatal error "Cannot redeclare function").

No se ha tocado nada de lo verificado como correcto: SQL preparado, recálculo en servidor del
permiso de acompañante, nombres de columnas/opciones, nonces/capabilities — todo bien en la
revisión.

## Cambios del 2026-09-07: tipografías de Belén + pase de diseño elegante (ya subidos y confirmados en producción)

- **Fuentes cambiadas a Adobe Fonts (Typekit)**, kit `https://use.typekit.net/gff0pqi.css` de la
  cuenta de Belén (ver conversación de WhatsApp reenviada, `cxonversacion.jpg` en la raíz del
  proyecto). Dos familias en el kit:
  - **`altesse-std-24pt`** (una sola variante, 400 regular) → titulares y nombres
    (`--boda-fuente-titulares`). Muy ornamentada: no tiene variante negrita, así que `h1,h2,h3`
    fijan `font-weight: 400` explícito para que el navegador no la "engorde" con negrita falsa.
  - **`the-seasons`** (300/400/700 + itálicas) → cuerpo (`--boda-fuente-cuerpo`). El primer
    párrafo de "Nuestra historia" usa el peso 300 (light) para un aire más editorial.
  - **Importante para Belén**: tiene que añadir `bodabelennai.es` como dominio autorizado del kit
    desde su cuenta de Adobe, o el navegador ignora las fuentes y cae al respaldo (Georgia).
  - Se borraron `assets/css/fonts.css` y toda la carpeta `assets/fonts/` del theme (las Google
    Fonts autoalojadas de antes, ya no se usan). `functions.php` ahora encola
    `https://use.typekit.net/gff0pqi.css` directamente y precontecta a `use.typekit.net` /
    `p.typekit.net` en `wp_head` en vez de precargar `.woff2` locales.
- **Legibilidad sobre el fondo floral**: con "The Seasons" (más fina que la Lora anterior) el
  texto costaba de leer directamente sobre el patrón, aunque estuviera al 60% de opacidad — se
  añadió una tarjeta semitransparente (`rgba(250,247,242,.92)`) a `.boda-seccion__contenido`, con
  un filete degradado dorado en el borde superior (misma idea aplicada también a las cajas de la
  cuenta atrás). La portada no lleva tarjeta (ya tenía su degradado + cajas blancas de la cuenta
  atrás); ahí se usa `text-shadow` en su lugar sobre nombres/fecha/saludo.
- **Pase de diseño "invitación sobre tela"** (aprobado por el usuario tras ver una vista previa en
  Artifact con las fuentes y el fondo reales): filete dorado en tarjetas y cuenta atrás, regla
  dorada centrada bajo cada título de sección (`.boda-seccion__titulo::after`), números de la
  cuenta atrás con `font-variant-numeric: tabular-nums`, menú de escritorio en versalitas con
  tracking (el menú móvil se deja grande y sin versalitas, más fácil de tocar), y las etiquetas
  "Ceremonia"/"Banquete" reestilizadas como texto pequeño en mayúsculas en vez de titulares
  grandes (reaprovechando los `<h3>` que ya existían, sin tocar ningún PHP). Nuevo token de color
  `--boda-anil-suave` (#5c6f92) para texto secundario/etiquetas.
  - Simplificaciones respecto a la maqueta de la vista previa: sin divisor con rombo entre
    secciones (ya había suficiente ritmo con el filete dorado + el fondo asomando entre tarjetas);
    "Nuestra historia" no se fuerza a dos columnas texto+imagen porque admite una galería de
    varias fotos, no siempre una.
  - Todo esto son cambios solo de `assets/css/variables.css` y `assets/css/main.css` (más
    `functions.php` para las fuentes) — ninguna plantilla PHP nueva ni tocada más allá de eso.

## Fallos detectados en testeo real 2026-09-08 (corregidos)

- **`is_page_template('page-boda.php')` no es fiable en la portada estática**: `countdown.js` y
  `nav-anchors.js` estaban condicionados a esa comprobación en `boda_theme_enqueue_assets()`
  (`functions.php`) y por eso NUNCA se cargaban en `bodabelennai.es` (confirmado con `curl`: la
  etiqueta `<script>` no aparecía ni en el HTML) — la cuenta atrás mostraba `--` fijo y el menú
  móvil no abría. Se quitó la condición: los dos scripts se cargan siempre (ya comprueban ellos
  mismos si sus elementos existen en la página, así que es seguro). **Si en el futuro se vuelve a
  usar `is_page_template()` para cargar algo solo en la portada, probarlo primero contra la
  portada estática real, no solo contra `/boda/` con la URL directa.**
- **Costura visible del patrón floral cada 600px** ("se ven los cuadrados"): la foto
  `fondo-boda-pattern.jpg` no es una textura pensada para repetir sin unión, así que con el velo
  al 60% (elegido antes de ver el sitio real) se notaba el borde de cada repetición. Subido el
  velo a **88% de opacidad** en `body` (`assets/css/main.css`) — deja solo una insinuación del
  patrón y la costura deja de notarse.

## Fallo crítico del RSVP detectado 2026-10-02 (corregido, subido y confirmado en producción)

- **El formulario no se podía enviar en producción**: la portada salía cacheada por LiteSpeed
  (`X-LiteSpeed-Cache: hit`) con un nonce caducado → `admin-ajax.php` respondía `-1` (403) y el
  invitado veía "problema de conexión". Causa: la página "Boda" (id 6) **no tiene asignada la
  plantilla en el editor** (`"template": ""` en la REST API); se pinta con `page-boda.php` solo
  porque su slug es `boda` (jerarquía `page-{slug}.php`), así que `is_page_template()` es falso
  y la exclusión de caché no se aplicaba. **No cambiar el slug de la página "Boda".**
- Arreglos: `class-rsvp-boda-cache.php` decide por `template_include` (plantilla real) y
  `rsvp_boda_render_form()` además marca no-cache para LiteSpeed; el AJAX devuelve un nonce
  nuevo si el recibido caducó y el JS reintenta una vez; assets del plugin versionados por
  `filemtime()`; quitado `esc_html()` del mensaje de éxito (doble escape con apóstrofos);
  límite de acompañantes 0 ya no se convierte en 6 en el JS; `countdown.js` sin ReferenceError
  cuando la fecha ya pasó.

- **Azulejo sevillano eliminado del theme** (2026-10-02, a petición del usuario): borrados
  `assets/css/azulejo-pattern.css` e `inc/svg-azulejo.php`, y quitadas las llamadas a
  `boda_theme_pintar_azulejo()` en portada/ceremonia/regalo y el enqueue en `functions.php`.

- **Velo del fondo floral bajado a 40%** (`main.css`, `body`): el usuario lo eligió el
  2026-10-02 sabiendo que con menos velo puede volver a notarse la costura del patrón cada 600px.
  El texto ya es legible por las tarjetas de sección. No volver a subirlo sin preguntar.

## Cambios del 2026-10-06 (subidos por FTP y verificados el 2026-10-06; Brevo funcionando el 2026-10-08)

- **Dos enlaces generales** (decisión del usuario: en vez de dar de alta a cada invitado):
  la web a secas = sin +1; `?c=<clave>` = con +1. La clave es aleatoria, se guarda en la opción
  `rsvp_boda_clave_mas_uno` (aparte de `rsvp_boda_settings` para que guardar ajustes no la borre)
  y se vuelve a comprobar en el AJAX (campo oculto `acceso`). Se muestran arriba en
  Boda → Invitados y enlaces. Para que la web a secas sea de verdad "sin +1", el ajuste
  `permite_acompanantes_sin_enlace` tiene que estar desmarcado (la pantalla avisa si no).
  Los enlaces personales `?i=slug` siguen funcionando igual y tienen prioridad.
- **Canción visible en el panel**: columna "Canción", vista "Con canción" y lista desplegable
  "Canciones pedidas" con botón de copiar (para el DJ) en Boda → Confirmaciones. Antes solo
  estaba en la BD/CSV.
- **Correos**: aviso a la pareja distingue "Nueva confirmación: Nombre" de "Cambio en una
  confirmación: Nombre" (y lo mismo en el correo al invitado); `Reply-To` al invitado; el motivo
  real del fallo (`wp_mail_failed`) se guarda en `email_error`; arreglado que a quien no dejaba
  email se le marcaba "Fallo de envío"; puerto 465 usa SSL; botón "Enviar correo de prueba" al
  final de Ajustes generales. Brevo configurado: dominio autenticado (TXT brevo-code, DKIM brevo1/brevo2, DMARC), buzón `boda@bodabelennai.es` creado en Hostinger, correo de prueba recibido. Pendiente: verificar el remitente `boda@bodabelennai.es` en Brevo (mientras no lo esté, Brevo lo reescribe como `boda@12374477.brevosend.com`).

## Qué dar de baja cuando acabe la boda (lista pedida por el usuario el 2026-10-08)

Ordenado por importancia: primero lo que cuesta dinero, luego datos personales, luego el resto.

**Cuesta dinero (se renueva solo si no se cancela):**
1. **Hosting de Hostinger**: hPanel → Facturación → Suscripciones → desactivar la renovación
   automática del plan de hosting.
2. **Dominio `bodabelennai.es`**: misma pantalla de suscripciones, desactivar la renovación
   automática del dominio (o dejarlo caducar). Si se quiere conservar la web de recuerdo, mantener
   solo esto y el hosting.
3. **Buzón `boda@bodabelennai.es`** (hPanel → Emails): es gratuito con el hosting, pero muere con
   él. Antes, revisar si hay respuestas de invitados que guardar.

**Datos personales de invitados (teléfonos, emails, alergias = datos de salud):**
4. Exportar a CSV lo que se quiera guardar (Boda → Confirmaciones → Exportar a CSV).
5. Borrar los datos: el plugin solo borra sus tablas al desinstalarlo si antes se añade
   `define( 'RSVP_BODA_BORRAR_AL_DESINSTALAR', true );` en `wp-config.php` (sin eso, desinstalar
   NO borra nada, a propósito). Con la constante puesta: Plugins → desactivar "RSVP Boda" → Borrar.
   Si se cancela el hosting entero, los datos se van con él igualmente.
6. **Brevo** guarda copia de los correos enviados (nombres y emails de invitados): borrar la
   cuenta en app.brevo.com → tu nombre → Ajustes de cuenta → eliminar cuenta (cuenta de
   la cuenta de Gmail con la que se registró en Brevo, plan gratuito, no cobra nada).

**Gratis, pero conviene cerrarlo:**
7. **Brevo**: si no se borra la cuenta, al menos borrar la clave SMTP "BodaBelenNai" (SMTP y API
   → SMTP; caduca sola el 2027-10-06).
8. **Registros DNS de Brevo** en hPanel → Dominios → DNS (solo si se mantiene el dominio): TXT `@`
   `brevo-code:…`, CNAME `brevo1._domainkey`, CNAME `brevo2._domainkey`, TXT `_dmarc`.
9. **Adobe Fonts** (cuenta de Belén): quitar `bodabelennai.es` de los dominios del kit `gff0pqi`,
   o borrar el kit.
10. **GitHub**: el repo `Gintoki64/boda-belen-nai` es público — archivarlo o pasarlo a privado
    (Settings → General → Danger Zone).
11. **FileZilla**: borrar el sitio guardado "Boda Belen y nai" (guarda la contraseña FTP en el PC).
12. Borrar la copia local de `public_html` con `wp-config.php` real si vuelve a aparecer por aquí.

## Cosas ya resueltas durante el despliegue (por si se repiten)

- **Hostinger tiene DOS capas de caché independientes**: la de LiteSpeed/WordPress (la que se
  purga desde la sección "Caché" de hPanel) y un CDN de borde (`hcdn`, visible en la cabecera
  `Server: hcdn` / `x-hcdn-cache-status` de cualquier respuesta) con `Cache-Control: max-age=604800`
  para estáticos. Purgar solo la primera no basta: si tras subir cambios el sitio no se actualiza
  pero **el modo "Personalizar" del Customizer de WordPress sí muestra la versión nueva** (el
  Customizer siempre pide la página sin caché), es justo esta segunda capa — buscar "CDN" en
  hPanel (aparte de "Caché") y purgar ahí también, o esperar/probar desde otra red.
- **Al subir carpetas por FTP (FileZilla) o extraer un zip en el Administrador de archivos, es
  fácil crear una carpeta duplicada anidada** (`themes/astra-child-belen-nai/astra-child-belen-nai/...`)
  si el panel remoto/la ubicación de extracción ya está *dentro* de la carpeta destino en vez de
  en su carpeta padre — pasó dos veces en el despliegue del 2026-09-07 (plugin y theme). Antes de
  arrastrar/extraer, comprobar siempre que la ruta mostrada es la carpeta **padre** (p. ej.
  `wp-content/themes`, no `wp-content/themes/astra-child-belen-nai`). Para diagnosticar desde
  fuera sin depender de lo que el usuario cree ver: pedir con `curl` la ruta anidada sospechosa
  directamente (`.../astra-child-belen-nai/astra-child-belen-nai/...`) — si responde 200, ahí está
  el problema.
- Al principio se subió solo `wp-content` sin el resto de WordPress → daba una página de error
  genérica ("This Page Does Not Exist") en cualquier URL de `/wp-admin`. Se resolvió instalando
  WordPress de verdad desde hPanel.
- El usuario no es muy familiar con la navegación del admin de WordPress (confundió rutas de
  archivos con URLs del admin, y no sabía distinguir un tema que hay que buscar en el repositorio
  de WordPress.org de uno que ya está subido a mano) — dar pasos muy explícitos y verificables
  (qué menú, qué icono, qué texto busca) en vez de dar por hecho que sabe moverse por wp-admin.
- Se descargó una copia completa de `public_html` (WordPress real de Hostinger, con
  `wp-config.php` y claves reales) a esta carpeta local para poder inspeccionarla — **contiene
  credenciales reales de producción**, no subir esta carpeta a git ni a ningún sitio tal cual.
- Verificado con `diff -rq` que los archivos subidos a Hostinger son idénticos a los de este
  repo local (sin corrupción en la subida por gestor de archivos).

## Archivos que no están en el repo (generados/pendientes)

- `assets/images/og-default.jpg` en el theme: imagen de repuesto generada con Pillow
  (1200x630, "Belén & Nai"), sustituir por foto real desde Ajustes generales cuando la tengan.
- `rsvp-boda.zip` y `astra-child-belen-nai.zip` en `wp-content/plugins/` y `wp-content/themes/`
  respectivamente: zips listos para subir vía el uploader de WordPress si hace falta volver a
  desplegar.
