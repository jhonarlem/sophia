# Cómo replicar el análisis de calidad de SOPHIA

Hola. En este documento dejé todo lo que hice para sacar las métricas de calidad del proyecto, con
los comandos tal cual los escribí y los errores con los que me choqué. La idea es que cualquiera del
grupo pueda repetirlo en su computador, aunque nunca haya usado Linux ni una terminal.

Yo trabajo en Arch Linux, pero la mayoría del grupo usa Windows con Ubuntu instalado por WSL, así
que la guía tiene las dos rutas. Donde cambia algo, lo aclaro con **Ubuntu/WSL** y **Arch**.

No hay que ser experto. Si vas copiando y pegando los bloques en orden, en una hora más o menos
tienes todo funcionando. La primera vez es la que más demora, porque hay que descargar bastante.

> **Si solo quieres ver los resultados** y no repetir el análisis, abre
> `REPORTE_CALIDAD_ACA.md`. Ahí están las tablas, las conclusiones y las capturas. Esta guía es para
> el que quiera volver a correr las herramientas desde cero.

---

## Antes de arrancar: qué vamos a hacer y qué significan las palabras raras

Voy a usar cinco herramientas. Ninguna modifica el código, todas solo lo leen. Es como pasarle un
escáner al proyecto.

| Herramienta | Qué hace, en palabras simples |
|---|---|
| **Larastan** | Es un corrector de ortografía, pero en vez de revisar cómo escribes, revisa cómo usas los tipos de datos (textos, números, listas). Si algo puede explotar, te lo marca. |
| **PhpMetrics** | Es como una báscula y un nutriólogo. Mide qué tan pesadas y enredadas están las clases, y pone una nota de qué tan fácil es mantenerlas. |
| **scc** | Es un contador. Cuenta cuántas líneas de código, cuántos comentarios y cuántos archivos hay. |
| **SonarQube** | Es el revisor que se lee todo el proyecto y lo deja en una página web con semáforos, deudas y listas de problemas. |
| **Docker** | Es una caja donde viene un programa ya armado. Nos sirve para no instalar SonarQube a mano, que es un dolor de cabeza. |

Y estas palabras van a salir todo el tiempo:

- **Terminal**: la ventana negra donde se escriben comandos. En Windows la vas a usar dentro de
  Ubuntu (WSL), no en PowerShell.
- **Repositorio**: la carpeta del proyecto que está guardada en GitHub.
- **Dependencia**: un paquete que el proyecto necesita para funcionar, como una pieza que se atornilla.
- **Análisis estático**: revisar el código sin ejecutarlo. Como revisar una receta sin cocinar.
- **Deuda técnica**: todo lo que hay que arreglar después. Se mide en tiempo (por ejemplo, "2 días
  de trabajo").
- **Complejidad ciclomática**: qué tantos caminos distintos puede tomar una función. Si tiene muchos
  `if`, sube. Arriba de 15 ya se considera difícil de probar.
- **N+1**: un problema de base de datos. En vez de hacer una consulta para traer 50 registros, haces
  51. Se nota cuando las pantallas se ponen lentas.
- **Cobertura**: qué tanto del código está probado con pruebas automáticas. Si nadie escribió
  pruebas, es 0 %.

---

## Paso 1. Preparar el computador

### Si usas Windows

Abre **PowerShell como administrador** (clic derecho en el menú de inicio → "Terminal (admin)") y
escribe esto:

```powershell
wsl --install -d Ubuntu
```

Reinicia el computador cuando te lo pida. Al volver, se abre sola una ventana de Ubuntu y te pide
crear un usuario y una contraseña. Ojo: cuando escribas la contraseña no se ve nada en pantalla, ni
asteriscos. Es normal, así funciona Linux. Escribe y dale Enter.

Si ya tenías Ubuntu instalado, puedes saltarte ese paso. Para comprobar que está, abre la aplicación
"Ubuntu" desde el menú de inicio.

Una vez dentro de Ubuntu, actualiza todo:

```bash
sudo apt update && sudo apt upgrade -y
```

La primera vez que uses `sudo` te va a pedir tu contraseña. Es la misma que creaste hace un momento.

### Si usas Linux (Ubuntu, Debian, Mint...)

Abre la terminal y actualiza:

```bash
sudo apt update && sudo apt upgrade -y
```

### Si usas Arch (mi caso)

```bash
sudo pacman -Syu
```

> Consejo que me hubiera gustado tener: en Windows, guarda el proyecto dentro de Ubuntu
> (`/home/tu-usuario/`), **no** en el escritorio de Windows. Si lo pones en `/mnt/c/...` todo va
> lentísimo porque Ubuntu tiene que cruzar la frontera entre los dos sistemas en cada archivo.

---

## Paso 2. Instalar lo básico

Esto es Git (para bajar el proyecto), PHP (el lenguaje en que está hecho) y unas librerías que el
proyecto necesita.

**Ubuntu / WSL:**

```bash
sudo apt install -y git curl unzip php-cli php-mbstring php-xml php-curl php-zip php-gd php-intl php-bcmath php-mysql default-jre
```

**Arch:**

```bash
sudo pacman -S --needed git curl unzip php php-gd jre-openjdk
```

Ahora Composer, que es el que administra las dependencias de PHP. En Ubuntu el que viene en los
repositorios a veces está viejo, así que instalo el oficial:

```bash
php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
sudo php composer-setup.php --install-dir=/usr/local/bin --filename=composer
php -r "unlink('composer-setup.php');"
```

Comprobemos que quedó todo:

```bash
git --version
php --version
composer --version
java -version
```

Deberías ver algo como `git version 2.x`, `PHP 8.3.x` (o superior), `Composer version 2.x` y
`openjdk version 21`. Si alguno dice "command not found", vuelve al paso anterior y revisa que el
paquete se haya instalado sin errores.

---

## Paso 3. Bajar el proyecto

```bash
cd ~
git clone https://github.com/jhonarlem/sophia.git
cd sophia
git checkout aca-calidad-sotware
```

La última línea es importante: en esa rama está todo lo de la entrega (esta guía, el informe y las
capturas).

Ahora se instalan las dependencias del proyecto. Este paso descarga bastante y puede tardar varios
minutos:

```bash
composer install
```

Si te pregunta algo, dale Enter a la opción por defecto. Cuando termine, verás un montón de líneas
y al final algo como `Generating autoload files`.

Después hay que crear el archivo de configuración:

```bash
cp .env.example .env
php artisan key:generate
```

> Si solo vas a hacer el análisis, hasta aquí llegas con la base de datos. No necesitas crear ni
> llenar MySQL para medir calidad. Si además quieres abrir la aplicación en el navegador, ahí sí
> toca configurar la base de datos, pero eso es otro tema y no lo cubro acá.

---

## Paso 4. Instalar las herramientas del análisis

### 4.1 Las de PHP

Ya vienen declaradas en el proyecto, así que con el `composer install` del paso anterior quedaron
instaladas. Puedes comprobarlo:

```bash
./vendor/bin/phpstan --version
./vendor/bin/phpmetrics --version
```

Si por alguna razón te dicen que no existen, este es el comando que las agrega:

```bash
composer require --dev larastan/larastan phpmetrics/phpmetrics beyondcode/laravel-query-detector
```

### 4.2 scc (el contador de líneas)

Se descarga un solo archivo, no hay instalador:

```bash
cd /tmp
curl -L -o scc.tar.gz https://github.com/boyter/scc/releases/download/v4.1.0/scc_Linux_x86_64.tar.gz
tar xzf scc.tar.gz
sudo install scc /usr/local/bin/scc
scc --version
```

Tiene que responder `scc version 4.1.0`.

### 4.3 sonar-scanner (el que envía los datos a SonarQube)

```bash
cd /tmp
curl -L -o scanner.zip https://binaries.sonarsource.com/Distribution/sonar-scanner-cli/sonar-scanner-cli-8.2.0.6438-linux-x64.zip
sudo unzip -q scanner.zip -d /opt
sudo ln -sf /opt/sonar-scanner-8.2.0.6438-linux-x64/bin/sonar-scanner /usr/local/bin/sonar-scanner
sonar-scanner --version
```

Debe decir `SonarScanner CLI 8.2.0.6438`. Si se queja de Java, devuélvete al paso 2 e instala
`default-jre`.

### 4.4 Docker y SonarQube

**Ubuntu / WSL:**

```bash
sudo apt install -y docker.io
sudo systemctl enable --now docker
sudo usermod -aG docker $USER
```

Ese último comando le da permiso a tu usuario para usar Docker sin escribir `sudo` cada vez. **No
funciona en la terminal que ya tienes abierta.** Tienes que cerrarla y abrir otra, o escribir
`newgrp docker`. A mí me pasó y perdí media hora pensando que Docker estaba dañado.

Si estás en WSL y te sale `System has not been booted with systemd`, mira el problema 7 más abajo.

**Arch:**

```bash
sudo pacman -S --needed docker
sudo systemctl enable --now docker
sudo usermod -aG docker $USER
```

Ahora sí, levantamos SonarQube. Primero un ajuste que necesita por dentro:

```bash
sudo sysctl -w vm.max_map_count=262144
```

Y creamos la caja:

```bash
docker run -d --name sonarqube -p 9000:9000 sonarqube:lts-community
```

La primera vez descarga como 1 GB, así que paciencia. Para ver si ya arrancó:

```bash
docker ps
```

Tiene que aparecer un contenedor llamado `sonarqube` con estado `Up`. Pero "Up" no significa que ya
esté listo por dentro. Espera un minuto o dos y prueba:

```bash
curl http://localhost:9000/api/system/status
```

Cuando responda `{"status":"UP"}` ya puedes abrir el navegador en <http://localhost:9000>.

> En Windows, abre ese enlace en tu navegador normal (Chrome, Edge, Brave). WSL comparte los puertos
> con Windows, no tienes que hacer nada más.

Entra con:

```
usuario: admin
contraseña: admin
```

SonarQube te va a obligar a cambiar la contraseña de una vez. Pon una que recuerdes y guárdala.
Después, para que el scanner pueda subir los resultados, hay que crear una "llave" (token):

1. Arriba a la derecha, clic en tu letra de usuario → **My Account**.
2. Pestaña **Security**.
3. En "Generate Tokens" escribe `aca`, deja el tipo *User Token* y dale **Generate**.
4. Copia el texto que empieza por `squ_...`. **Solo se muestra una vez**, así que pégalo en un
   archivo de texto mientras tanto.

---

## Paso 5. Correr los análisis

Ubícate en la carpeta del proyecto:

```bash
cd ~/sophia
```

### 5.1 Larastan (el corrector de tipos)

```bash
./vendor/bin/phpstan analyse --no-progress > report_larastan.txt
```

Tarda un rato y **va a terminar con error**. Eso es lo esperado: el error significa "encontré
problemas". Al final del archivo debe decir algo como `Found 180 errors`.

Si se queda sin memoria:

```bash
php -d memory_limit=2G ./vendor/bin/phpstan analyse --no-progress > report_larastan.txt
```

Para leerlo sin marearte, abre el archivo y mira las últimas líneas:

```bash
tail -5 report_larastan.txt
```

### 5.2 PhpMetrics (la báscula)

```bash
mkdir -p storage/app/reports/phpmetrics
./vendor/bin/phpmetrics --report-html=storage/app/reports/phpmetrics .
```

Al terminar abre el informe en el navegador:

```bash
xdg-open storage/app/reports/phpmetrics/index.html
```

En Windows eso no funciona desde Ubuntu. Copia la ruta y ábrela desde el explorador, o hazlo así:

```bash
cp -r storage/app/reports/phpmetrics /mnt/c/Users/TU_USUARIO/Desktop/
```

Y abres `index.html` con doble clic desde Windows.

### 5.3 scc (el contador)

```bash
scc . > report_scc.txt
```

Y para verlo:

```bash
cat report_scc.txt
```

Te va a mostrar una tabla por lenguaje. La fila que importa es `PHP`.

### 5.4 SonarScanner (el que manda todo a la página web)

```bash
sonar-scanner -Dsonar.host.url=http://localhost:9000 -Dsonar.token=AQUI_VA_TU_TOKEN
```

Reemplaza `AQUI_VA_TU_TOKEN` por el `squ_...` que copiaste. Al final tiene que decir
`ANALYSIS SUCCESSFUL` y darte un enlace como
`http://localhost:9000/dashboard?id=laravel-filament-aca`.

Si ves `EXECUTION FAILURE`, casi siempre es porque el token está mal copiado o SonarQube todavía no
había terminado de arrancar.

---

## Paso 6. Las capturas para el informe

El profesor normalmente pide evidencias. Estas son las que usé y para qué sirve cada una:

| Captura | Dónde se saca | Para qué |
|---|---|---|
| Proyecto en SonarQube | `http://localhost:9000/projects` | Demostrar que el análisis se subió |
| Dashboard | pestaña **Overview** del proyecto | Los números grandes: bugs, deuda, cobertura |
| Issues | pestaña **Issues** | La lista de 345 hallazgos |
| Complejidad | **Measures → Complexity** | El archivo más enredado |
| Deuda | **Measures → Maintainability → Debt** | Cuánto cuesta arreglar |
| Duplicación | **Measures → Duplications** | Código repetido |
| Código | pestaña **Code** | Métricas por carpeta |
| PhpMetrics resumen | `index.html` | Nota de mantenibilidad |
| PhpMetrics complejidad | `complexity.html` | Tabla de complejidad por clase |
| PhpMetrics acoplamiento | `coupling.html` | Qué tanto depende una clase de otras |

Para capturar:

- **Windows:** `Win + Shift + S` y seleccionas el área. Se guarda en el portapapeles.
- **Ubuntu con escritorio:** tecla `Impr Pant`, o busca "Captura de pantalla" en el menú.
- **Arch con Hyprland (lo que uso):** `grim -g "$(slurp)" ~/captura.png`.

Yo las guardé en `docs/capturas/` con nombres como `02-sonar-dashboard.png`, así el informe las
puede referenciar sin enredos.

---

## Paso 7. Problemas con los que me choqué (y cómo los resolví)

**1. `permission denied ... /var/run/docker.sock`**

Es el clásico. Docker quedó instalado, pero tu usuario todavía no tiene permiso. Ya lo arreglamos en
el paso 4.4 con `usermod`. Lo que casi nadie dice: hay que **cerrar la terminal y abrir otra**, o
escribir `newgrp docker`. El permiso no se aplica a la ventana que ya estaba abierta.

**2. El puerto 9000 está ocupado**

```bash
docker ps
```

Si hay otro contenedor usándolo, páralo con `docker stop NOMBRE`. Si el que está es SonarQube pero
quedó a medias:

```bash
docker restart sonarqube
```

**3. SonarQube arranca y se muere solo**

Casi siempre es el `vm.max_map_count`. Se aplica con el comando del paso 4.4. Para que no se pierda
al reiniciar el computador:

```bash
echo "vm.max_map_count=262144" | sudo tee /etc/sysctl.d/99-sonarqube.conf
sudo sysctl --system
```

**4. `composer: command not found`**

Se instaló pero la terminal no lo encuentra. Cierra la terminal y ábrela de nuevo. Si sigue, revisa
que el archivo exista:

```bash
ls -l /usr/local/bin/composer
```

**5. Composer se queja de una extensión de PHP**

Por ejemplo `ext-zip` o `ext-gd`. Instálala y repite:

```bash
sudo apt install -y php-zip php-gd
```

**6. Composer bloquea la instalación por "security advisories"**

Esto me pasó. Las versiones nuevas de Composer se niegan a instalar paquetes con vulnerabilidades
conocidas. Es una buena práctica, pero para el trabajo académico necesitamos continuar. Se agrega
`--no-blocking` al final:

```bash
composer require --dev larastan/larastan phpmetrics/phpmetrics beyondcode/laravel-query-detector --no-blocking
```

Ojo: eso no significa que las vulnerabilidades no existan. De hecho, en este proyecto hay 32 avisos
y 3 son críticos. Está documentado en el informe, en la parte de seguridad.

**7. En WSL: `System has not been booted with systemd`**

WSL por defecto no enciende el sistema de servicios. Se habilita creando un archivo. En Ubuntu:

```bash
sudo nano /etc/wsl.conf
```

Pega esto y guarda con `Ctrl+O`, Enter, `Ctrl+X`:

```ini
[boot]
systemd=true
```

Luego, **en PowerShell** (no en Ubuntu):

```powershell
wsl --shutdown
```

Y vuelve a abrir Ubuntu. Ahora `sudo systemctl start docker` funciona.

**8. WSL se queda sin memoria con SonarQube**

SonarQube pide bastante. En Windows crea el archivo `C:\Users\TU_USUARIO\.wslconfig` con:

```ini
[wsl2]
memory=6GB
```

Después `wsl --shutdown` en PowerShell y vuelve a entrar.

**9. `sonar-scanner` dice que no encuentra Java**

```bash
sudo apt install -y default-jre
```

Y comprueba con `java -version`.

---

## Paso 8. Cómo leer los resultados sin ser experto

No necesitas entender cada número. Con esto te alcanza para explicar el informe:

- **Larastan:** cada error es un lugar donde el código puede fallar. 180 no significa que la app
  esté rota (de hecho funciona), sino que hay cosas sin "etiquetar" y eso hace difícil detectar
  fallos reales. La mayoría son propiedades de modelos que no dicen qué tipo son.
- **MI (Maintainability Index):** va de 0 a 100 (a veces pasa de 100 porque PhpMetrics regala puntos
  por comentarios). Arriba de 75 se considera mantenible. El proyecto dio 75,6, o sea que pasa, pero
  raspando.
- **Complejidad (v(G)):** mientras más alto, más difícil es probar esa función. El límite sano es 15.
  Aquí la peor llegó a 29, en el método que crea una venta.
- **Deuda técnica:** SonarQube la traduce a tiempo. "2d 7h" significa que alguien necesitaría como
  dos días y medio de trabajo para dejar limpio lo que marca.
- **Cobertura 0 %:** nadie escribió pruebas automáticas. Por eso el informe dice que la capacidad de
  prueba está en rojo.
- **N+1:** se detecta mirando el código de las tablas de Filament. Si una tabla muestra
  `cliente.nombre` y no se cargó el cliente de antemano, Laravel va a la base de datos una vez por
  cada fila.

---

## Paso 9. Lista de chequeo

Antes de decir "ya terminé", revisa:

- [ ] `php --version` y `composer --version` responden.
- [ ] `composer install` terminó sin errores.
- [ ] `docker ps` muestra `sonarqube` en `Up`.
- [ ] `curl http://localhost:9000/api/system/status` responde `UP`.
- [ ] `report_larastan.txt` termina con `Found 180 errors`.
- [ ] `storage/app/reports/phpmetrics/index.html` abre y muestra gráficas.
- [ ] `report_scc.txt` tiene la tabla con la fila `PHP`.
- [ ] `sonar-scanner` terminó con `ANALYSIS SUCCESSFUL`.
- [ ] El dashboard del proyecto se ve en el navegador.
- [ ] Tienes tus capturas guardadas.

Si algo no cuadra, los números pueden variar un poquito (por ejemplo 179 o 181 errores) si alguien
tocó el código después. Lo importante es que el orden de magnitud coincida.

---

## Para el profesor

Este trabajo evalúa el proyecto **SOPHIA** (Laravel 11 + FilamentPHP 3 + MariaDB) contra la norma
**ISO/IEC 25010**, con cinco herramientas y evidencia reproducible:

| Aspecto | Herramienta | Resultado |
|---|---|---|
| Errores estáticos | Larastan 3.13.2 / PHPStan 2.3.2, nivel 5 | 180 errores |
| Mantenibilidad y complejidad | PhpMetrics 2.11.0 | MI 75,6 · v(G) máx. 29 |
| Tamaño y comentarios | scc 4.1.0 | 11.952 líneas de código PHP |
| Deuda, duplicación y cobertura | SonarQube 9.9.8 LTS | 319 code smells · 15,3 % duplicación · 0 % cobertura |
| Consultas N+1 | Query Detector + revisión de código | 24 de 25 relaciones sin carga anticipada |
| Seguridad de dependencias | Composer Audit | 32 avisos, 3 críticos |

El informe completo, con la matriz ISO 25010, el desglose por componente de Filament y el plan de
refactorización priorizado, está en **`REPORTE_CALIDAD_ACA.md`**. Las capturas que respaldan cada
número están en **`docs/capturas/`** y se referencian en la sección 8 de ese documento.

Los comandos de esta guía son los mismos que produjeron esos resultados. Cualquier persona con
Ubuntu (o WSL) puede repetirlos en orden y llegar al mismo tablero.

---

## Apéndice: comandos sueltos, por si se pierden

```bash
# Entrar al proyecto
cd ~/sophia

# Ver si SonarQube está vivo
curl http://localhost:9000/api/system/status

# Ver el contenedor
docker ps

# Detener / arrancar SonarQube
docker stop sonarqube
docker start sonarqube

# Volver a correr todo
./vendor/bin/phpstan analyse --no-progress > report_larastan.txt
./vendor/bin/phpmetrics --report-html=storage/app/reports/phpmetrics .
scc . > report_scc.txt
sonar-scanner -Dsonar.host.url=http://localhost:9000 -Dsonar.token=TU_TOKEN

# Ver la rama en la que estás
git branch --show-current
```
