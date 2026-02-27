# Estructura reorganizada (Odontograma Visual)

## Carpetas principales
- **/public**: vistas web (HTML/PHP) + assets (css/js). Aquí vive el odontograma visual.
- **/api**: endpoints PHP (incluye odontograma + módulos como pacientes/citas/pagos) y soporte (config/auth/middleware).
- **/database**: scripts SQL del proyecto.
- **/docs**: documentación.

## Notas
- Se movieron los archivos de `/frontend` a `/public`.
- Se unificaron los endpoints que estaban en `Base de datos/api` dentro de `/api`.
- Se conservó el material anterior en `/legacy` por si ocupas comparar o recuperar algo.
