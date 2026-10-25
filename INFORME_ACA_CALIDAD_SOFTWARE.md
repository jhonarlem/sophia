# PORTADA

- **Título de la Actividad:** Evaluación de la Calidad de un Software
- **Asignatura:** Calidad de Software
- **Presentado por:** Jhon Arlem Silva Andrade
- **Stack del Proyecto Evaluado:** Laravel 11.57 (línea 10+), FilamentPHP 3.3, MySQL/MariaDB 13.0
- **Fecha:** Octubre de 2026

# CONTENIDO DEL TRABAJO

## 1. EVALUACIÓN DE LA CALIDAD DEL SOFTWARE

El objeto de evaluación es **SOPHIA**, un sistema de punto de venta e inventario construido sobre
Laravel 11.57, FilamentPHP 3.3 y MySQL/MariaDB 13.0. El alcance del análisis cubre las 95 clases de
la carpeta `app/` (5.840 líneas de código), los 120 archivos indexados por SonarQube y las
plantillas Blade de impresión.

La evaluación combina tres mediciones complementarias: conteo físico de código con **scc 4.1.0**,
métricas orientadas a objetos con **PhpMetrics 2.11.0** y análisis estático de tipos con
**Larastan 3.13.2** sobre PHPStan 2.3.2 en nivel 5. Los resultados se interpretan contra las
características de la norma ISO/IEC 25010, en especial mantenibilidad, fiabilidad, complejidad y
eficiencia de rendimiento.

### a) Normas y Estándares de Calidad (ISO/IEC 25010)

La norma ISO/IEC 25010 define ocho características de calidad de producto. Para este trabajo se
priorizan cuatro que pueden medirse de forma objetiva con las herramientas disponibles.

**Mantenibilidad.** El proyecto tiene **11.952 SLOC de PHP** distribuidos en 164 archivos y
**3.235 líneas de comentarios**, lo que equivale a un **ratio de documentación del 21,3 %** sobre el
total de líneas escritas (código más comentarios). Ese porcentaje supera el mínimo del 15 %
habitual, aunque el 40 % de esos comentarios se concentra en pocas clases y no en los componentes
más complejos. Las plantillas Blade suman 2.068 SLOC adicionales en solo 8 archivos.

El análisis de acoplamiento de PhpMetrics arroja un **acoplamiento eferente promedio Ce = 5.76**,
un acoplamiento aferente **Ca = 1.24** y una **inestabilidad promedio I = 0.80**. La inestabilidad
se calcula como `I = Ce / (Ca + Ce)` y mide la proporción de dependencias salientes: cuanto más
cerca de 1, más frágil es la clase frente a cambios externos. Un valor global de 0.80 indica que el
sistema depende mucho de sus colaboradores y que pocas clases dependen de él, comportamiento típico
de una capa de presentación que concentra orquestación. Los modelos son la excepción positiva
(Ca = 6.17, I ≈ 0.21) y los Resources de Filament el punto crítico (Ce = 18.60, I = 0.97).

Como referencia complementaria, el **Maintainability Index ponderado por LOC es 75.59**, justo en
el límite del umbral de 75: el sistema se considera mantenible, pero sin margen. El 19 % de las
clases (18 de 95) está por debajo de 65.

**Fiabilidad y Corrección.** Larastan en nivel 5 reporta **180 observaciones**: 179 errores
distribuidos en 42 archivos y 1 error global de configuración. El desglose por regla es el
siguiente:

- `property.notFound` (76) y `property.nonObject` (42): accesos a propiedades dinámicas de Eloquent
  sobre tipos que el analizador no puede resolver. La causa raíz es que las relaciones de los 12
  modelos se declaran sin tipo de retorno (`public function box()` en lugar de
  `public function box(): BelongsTo`).
- `match.unhandled` (13): expresiones `match` sin rama `default` sobre estados de base de datos.
  Todas están en componentes de Filament, por ejemplo `BoxResource.php:109` y
  `SalesWidget.php:40`. En producción, un estado nuevo lanzaría `UnhandledMatchError`.
- `property.phpDocType` (13): anotaciones `@property` que no coinciden con la realidad del modelo.
- **`binaryOp.invalid` (4), tres de ellos en `SaleFormBuilder.php` (líneas 153, 184 y 418):**
  divisiones entre `(float|int)` y `(array<string>|string)`. Son operaciones financieras que, con
  datos inesperados, producen un `TypeError` en tiempo de ejecución.
- `larastan.relationExistence` (4): relaciones que el analizador no reconoce, por ejemplo
  `Credit::sale` usada desde `CustomerInfo.php:53`.
- `nullCoalesce.expr` (7) y `varTag.misplaced` (5): expresiones redundantes y anotaciones mal
  ubicadas.

Estas observaciones no implican que la aplicación esté caída: el sistema arranca y opera. Lo que
indican es que el compilador estático no puede garantizar el contrato de tipos, de modo que los
errores se desplazan al momento de ejecución, que es el escenario más costoso de corregir.

**Complejidad.** La **complejidad ciclomática promedio por clase es v(G) = 4.83**, un valor bajo y
saludable. scc acumula **268 puntos de complejidad en PHP** a nivel de archivo, mientras que
PhpMetrics suma 459 al contar por clase y método. La diferencia es de granularidad, no de
contradicción. El promedio esconde los casos extremos: **cuatro métodos superan el umbral de 15**,
con un máximo de **v(G) = 29 en `SaleManager::createSale`** y 27 en
`SaleFormBuilder::handleBarcodeScan`. El 80 % de la complejidad se concentra en el 5 % de las
clases, justamente las que gestionan ventas, pagos y caja.

**Eficiencia de Rendimiento (MySQL y Eloquent).** Se revisaron las consultas que generan los
componentes de Filament. El patrón de riesgo es el **N+1**: Filament resuelve
`TextColumn::make('relacion.atributo')` con carga diferida por cada fila, de modo que un listado de
`n` registros ejecuta `1 + k·n` consultas si no se declara `getEloquentQuery()->with([...])`.

La medición estática encontró **25 columnas de relación repartidas en 12 clases** (Resources,
RelationManagers y Widgets) y **solo una cubierta con carga anticipada** (`SaleResource` precarga
`box.resolution`, pero no `customer` ni `user`). Los casos más costosos son `SalesWidget`, que
ejecuta **16 consultas para mostrar 5 ventas** (en lugar de 2), y `BoxWidget`, que invoca
`$box->sales->count()` y con ello **hidrata toda la colección** de ventas solo para contarlas.
También se detectó que `Credit::payments()` usa una clave foránea no convencional
(`hasMany(Payment::class, 'sale_id', 'sale_id')`), lo que puede mezclar pagos de distintas ventas.

El detector `beyondcode/laravel-query-detector` quedó instalado y activo en el entorno local. La
medición en ejecución no pudo completarse porque la base de datos de desarrollo está vacía (0
ventas, 0 cajas, 1 cliente), por lo que la evidencia presentada es estática y debe confirmarse en
un entorno con datos representativos. Aun así, la revisión del código de consulta es concluyente
sobre la existencia del patrón.

### b) Matriz Cuantitativa de Métricas de Calidad

| Característica ISO 25010 | Métrica Evaluada | Herramienta | Valor Medido | Criterio de Aprobación | Estado |
| :--- | :--- | :--- | :--- | :--- | :--- |
| Mantenibilidad / Volumen | SLOC PHP / Comentarios | scc | 11.952 SLOC / 3.235 Comentarios | > 15% comentarios | Aprobado |
| Mantenibilidad / Arquitectura | Inestabilidad Promedio (I) | PhpMetrics | 0.80 | I < 0.70 | Requiere Mejora |
| Complejidad Ciclomática | v(G) Promedio por clase | PhpMetrics | 4.83 | v(G) < 15 | Aprobado |
| Fiabilidad / Tipado Estático | Errores en Nivel 5 | Larastan | 180 observaciones | 0 observaciones | Crítico |
| Calidad Estructural | Violaciones (Error / Warning) | PhpMetrics | 26 Errores / 20 Warnings | 0 Errores | Requiere Mejora |

La matriz resume el diagnóstico: **dos características aprobadas, dos que requieren mejora y una en
estado crítico**. El volumen y la complejidad media están bajo control; el problema no es cuánto
código hay, sino cómo está tipado y cómo se distribuyen las responsabilidades. La fila de
fiabilidad es la única en rojo y, a la vez, la más fácil de revertir: el 68 % de sus 180
observaciones se corrige tipando las relaciones de los modelos.

### c) Enfoque DevOps y Entornos Cloud

El análisis se ejecutó sobre una instancia local de **SonarQube 9.9.8 LTS Community** levantada con
Docker, lo que garantiza que cualquier integrante del equipo obtenga el mismo entorno con un solo
comando:

```bash
docker run -d --name sonarqube -p 9000:9000 sonarqube:lts-community
```

La instancia queda disponible en `http://localhost:9000`. El envío de resultados se hace con
**SonarScanner 8.1**, que lee la configuración declarativa del archivo `sonar-project.properties`:

```properties
sonar.projectKey=laravel-filament-aca
sonar.projectName=Proyecto Laravel Filament ACA
sonar.projectVersion=1.0
sonar.sources=app,config,routes,resources/views
sonar.exclusions=vendor/**,node_modules/**,storage/**,tests/**
sonar.sourceEncoding=UTF-8
sonar.php.exclusions=vendor/**
```

Ese archivo define el alcance (código propio, sin dependencias ni artefactos) y hace que el análisis
sea reproducible. El comando de publicación es:

```bash
sonar-scanner -Dsonar.host.url=http://localhost:9000 -Dsonar.token=$SONAR_TOKEN
```

Los resultados obtenidos fueron: **9.880 líneas de código analizadas, 0 vulnerabilidades, 319 code
smells, 26 bugs y una deuda técnica de 1.429 minutos** (2 días y 7 horas con jornada de 8 horas).
La duplicación se sitúa en **15,3 %** y la **cobertura en 0 %**. El Quality Gate por defecto
aparece como aprobado porque evalúa solo el código nuevo, no el histórico; si se aplicara a todo el
proyecto, la cobertura y la duplicación lo harían fallar.

En un escenario de integración continua, este mismo flujo se traslada a un pipeline. Un runner
Linux instala PHP y Composer, restaura la caché de dependencias, ejecuta `phpstan`, `phpmetrics` y
`sonar-scanner`, y publica los reportes como artefactos. El Quality Gate de SonarQube actúa como
puerta: si el análisis falla, el *merge* se bloquea. Para el despliegue, la arquitectura natural es
un `docker-compose` con tres servicios (aplicación PHP-FPM, MySQL/MariaDB y Nginx como proxy
inverso), variables sensibles inyectadas desde el gestor de secretos, migraciones ejecutadas de
forma controlada y un *health check* que valide la disponibilidad antes de enrutar tráfico. En
producción conviene separar *staging* y producción, y promover la misma imagen ya analizada para
evitar diferencias entre entornos.

## 2. IDENTIFICACIÓN DE TRES (3) OPORTUNIDADES DE MEJORA

### Oportunidad 1: Reforzamiento del Rigor de Tipado y Corrección de Operaciones Críticas

**Justificación.** Los 180 errores de Larastan no son homogéneos: 122 provienen de propiedades y
relaciones de Eloquent sin tipo, 13 de `match` no exhaustivos y 4 de operaciones aritméticas
inválidas. Las tres divisiones de `SaleFormBuilder.php` (líneas 153, 184 y 418) son las más
peligrosas porque participan en el cálculo de totales: si el operando llega como cadena o arreglo,
PHP lanza un `TypeError` y la venta se interrumpe. Los 13 `match` sin `default` tienen el mismo
perfil de riesgo: hoy funcionan porque los estados están acotados, pero un valor nuevo en la base
de datos rompe la pantalla.

**Impacto.** Alto. Afecta la ruta crítica del negocio (ventas, pagos y caja) y es la causa del
estado "Crítico" de la matriz.

### Oportunidad 2: Reducción del Acoplamiento e Inestabilidad en Componentes de FilamentPHP

**Justificación.** La inestabilidad promedio **I = 0.80** supera el umbral de 0.70, y en los
Resources de Filament llega a **0.97 con Ce = 18.60**. Eso significa que las clases de interfaz
conocen y orquestan demasiadas piezas: formularios, tablas, acciones, cálculos y persistencia. Las
**26 violaciones de nivel Error** de PhpMetrics se concentran en esas clases, y el Maintainability
Index global (75.59) queda en el límite. El caso extremo es `SaleFormBuilder`: 474 líneas, CCN total
de 115 y MI de 35.7. La consecuencia práctica es que cualquier cambio en una regla de negocio obliga
a tocar la capa de presentación, lo que encarece el mantenimiento y multiplica el riesgo de
regresión.

**Impacto.** Alto. Es la deuda estructural que explica por qué el sistema, aun con buena
complejidad media, se vuelve difícil de modificar.

### Oportunidad 3: Implementación de Cobertura de Pruebas Automatizadas y Pipeline CI/CD

**Justificación.** El proyecto no tiene suite de pruebas: los dos únicos tests son marcadores de
posición (uno verifica que `/` responda 200 y otro que `true === true`), por lo que la cobertura
reportada es **0 %**. Al mismo tiempo, `composer audit` detecta **32 avisos de seguridad en 11
paquetes, tres de ellos críticos**, incluido un bypass de autenticación en `laravel/framework` y un
RCE en `phpspreadsheet`. Actualizar esas dependencias sin pruebas de regresión es una operación de
alto riesgo, y no actualizarlas mantiene la vulnerabilidad abierta. La ausencia de cobertura es,
entonces, el factor que bloquea la evolución segura del proyecto.

**Impacto.** Crítico a mediano plazo. No afecta la funcionalidad actual, pero impide modernizar la
cadena de suministro y detectar regresiones.

## 3. PROPUESTA DE SOLUCIONES PARA MEJORAR LA CALIDAD

### Solución a la Oportunidad 1 (Tipado Estático)

1. Declarar el tipo de retorno en todas las relaciones de los 12 modelos y documentar las
   propiedades con PHPDoc:

```php
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** @property-read Box $box */
/** @property-read Customer $customer */
public function box(): BelongsTo
{
    return $this->belongsTo(Box::class);
}
```

2. Aplicar *casts* explícitos en los modelos para los campos monetarios y de cantidad
   (`'total' => 'float'`, `'stock' => 'integer'`), y usar `(float)` / `(int)` en las operaciones
   financieras de `SaleFormBuilder`, verificando que el dividendo nunca sea cero.
3. Añadir `default =>` a los 13 `match` sobre `status` y `payment_method`, con un valor de respaldo
   y registro en el log.
4. Incorporar Larastan nivel 5 como *pre-commit hook* y en el pipeline, de modo que ningún cambio
   aumente el número de errores.

**Resultado esperado:** reducción de 180 a cerca de 58 observaciones (las 122 de tipado desaparecen)
y eliminación del riesgo de `UnhandledMatchError` en producción. **Esfuerzo:** 2 a 3 horas.
**Riesgo:** nulo, porque solo se agregan tipos.

### Solución a la Oportunidad 2 (Patrón Service/Action)

1. Extraer de los Resources la lógica que no es de presentación. Los candidatos naturales son
   `BoxManager` (apertura y cierre de caja), `SaleService` (cálculo de totales y registro de venta),
   `InvoiceNumberService` (correlativo y resolución) y `PaymentService` (normalización de pagos).
2. Dejar en el Resource solo el esquema de formulario, la tabla y las acciones de interfaz, que es
   su responsabilidad original.
3. Mover los cálculos a métodos pequeños y testeables, con una única razón para cambiar.

```php
final class SaleService
{
    public function __construct(
        private InvoiceNumberService $invoiceNumber,
        private StockService $stock,
    ) {}

    public function register(array $data): Sale
    {
        return DB::transaction(function () use ($data) {
            $invoice = $this->invoiceNumber->next();
            $items   = $this->stock->reserve($data['items']);

            return Sale::create([...$data, 'invoice_number' => $invoice]);
        });
    }
}
```

**Resultado esperado:** bajar la inestabilidad de los Resources de 0.97 a valores **I < 0.50**,
reducir el acoplamiento eferente y subir el MI por encima de 80. **Esfuerzo:** 6 a 8 horas, con
pruebas de caracterización previas del flujo de venta. **Riesgo:** alto si no hay pruebas; por eso
esta solución debe ejecutarse después de la número 3.

### Solución a la Oportunidad 3 (DevOps y Pruebas Automatizadas)

1. Crear una suite con PestPHP (ya instalado) que cubra los flujos críticos: registro de venta,
   cálculo de totales, apertura y cierre de caja, y devolución de producto. Meta inicial: **40 % de
   cobertura en `app/Services` y `app/Models`**, que es donde vive la lógica transaccional.
2. Generar el reporte de cobertura en formato Clover y publicarlo en SonarQube:

```bash
php artisan test --coverage-clover=coverage.xml
sonar-scanner -Dsonar.php.coverage.reportPaths=coverage.xml
```

3. Configurar un pipeline de GitHub Actions que se ejecute en cada *pull request*:

```yaml
name: calidad
on: [push, pull_request]
jobs:
  analisis:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
      - uses: shivammathur/setup-php@v2
        with:
          php-version: '8.3'
          coverage: xdebug
      - run: composer install --no-interaction --prefer-dist
      - run: ./vendor/bin/phpstan analyse --no-progress
      - run: php artisan test --coverage-clover=coverage.xml
      - run: ./vendor/bin/phpmetrics --report-html=build/phpmetrics app
      - run: sonar-scanner
        env:
          SONAR_TOKEN: ${{ secrets.SONAR_TOKEN }}
          SONAR_HOST_URL: ${{ secrets.SONAR_HOST_URL }}
```

4. Añadir el Quality Gate como condición de *merge* y publicar los reportes como artefactos de cada
   ejecución.

**Resultado esperado:** cobertura medible y creciente, detección temprana de regresiones y
posibilidad de actualizar las dependencias vulnerables con red de seguridad. **Esfuerzo:** 8 a 12
horas para la primera versión del pipeline.

# CONCLUSIONES

1. **El proyecto es funcional y estructuralmente sano, pero su calidad no está verificada.** La
   complejidad promedio (v(G) = 4.83) y el volumen (11.952 SLOC) están dentro de rangos razonables;
   sin embargo, 180 observaciones estáticas y una cobertura del 0 % significan que el
   comportamiento correcto depende de que los datos de entrada sean siempre los esperados. El
   análisis estático convierte esa suposición en una lista concreta de riesgos, algo que las
   pruebas manuales no logran: encuentra rutas que hoy no se ejecutan pero que fallarían mañana con
   un estado nuevo o un campo vacío.

2. **La deuda principal es de diseño, no de tamaño.** El 68 % de los errores proviene de relaciones
   sin tipar y la inestabilidad de 0.80 de una capa de presentación que concentra reglas de negocio.
   Ninguna de las dos cosas se resuelve escribiendo más código, sino moviéndolo: tipar contratos y
   delegar responsabilidades. La refactorización hacia servicios reduce el acoplamiento y, como
   efecto secundario, hace testeable lo que hoy no lo es.

3. **La calidad debe automatizarse para sostenerse en el tiempo.** Una medición puntual sirve para
   el diagnóstico, pero no impide el deterioro. Integrar Larastan, PhpMetrics y SonarQube en un
   pipeline, con umbrales explícitos y Quality Gate como puerta de *merge*, convierte la calidad en
   una condición del proceso y no en una revisión heroica al final del semestre. Ese es el paso que
   separa un proyecto que "funciona" de un producto que puede mantenerse.

# REFERENCIAS BIBLIOGRÁFICAS

- ISO/IEC 25010:2011. *Systems and software engineering — Systems and software Quality Requirements
  and Evaluation (SQuaRE) — System and software quality models*. International Organization for
  Standardization.
- Otwell, T. (2026). *Laravel Documentation: Eloquent ORM & Testing*. Laravel LLC.
  https://laravel.com/docs
- FilamentPHP (2026). *Filament Documentation: Resources and Forms*. https://filamentphp.com/docs
- PHPStan / Larastan (2026). *Static Analysis for Laravel*. https://phpstan.org y
  https://github.com/larastan/larastan
- PhpMetrics (2026). *Static analyzer for PHP*. https://phpmetrics.org
- scc (2026). *Sloc, Cloc and Code*. https://github.com/boyter/scc
- SonarSource (2026). *SonarQube Documentation*. https://docs.sonarsource.com
