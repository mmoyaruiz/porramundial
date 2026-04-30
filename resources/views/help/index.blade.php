{{--
  Vista: Centro de ayuda en línea
  Propósito: Proporcionar guías y FAQs por perfiles (admin / usuario).
  Relación con rúbrica UD3: "Incluye un sistema de ayuda en línea" (obligatorio para apto).
--}}

@extends('layouts.app')

@section('content')
<div class="container">
  <h1>Centro de ayuda</h1>
  <p>Encuentra aquí respuestas rápidas y guías de uso.</p>

  <h1>1. Introducción</h1>

  <p>PORRAMUNDIAL.COM es una aplicación web que permite a los usuarios crear y participar en porras de fútbol, realizar pronósticos de partidos y campeones, y consultar clasificaciones actualizadas automáticamente.
    Esta sección de ayuda explica cómo utilizar la aplicación, paso a paso, desde el punto de vista del usuario.</p>

  <h1>2. Registro y acceso a la aplicación</h1>

  <p>Para utilizar la aplicación es necesario registrarse con un nombre de usuario y contraseña.</p>
  <p>Una vez registrado, el usuario puede iniciar sesión y acceder a todas las funcionalidades.</p>
  <p>Cada usuario puede participar en varias porras simultáneamente.</p>

  <h1>3. Porras</h1>
  <h2>3.1 Crear una porra</h2>

  <p>Un usuario puede crear una nueva porra asociada a una competición.</p>
  <p>Al crearla, el usuario se convierte en administrador de la porra.</p>
  <p>Se definen los criterios de puntuación (ganador, marcador exacto, campeones).</p>

  <h2>3.2 Unirse a una porra</h2>

  <p>Un usuario puede unirse a una porra existente si está disponible.</p>
  <p>Una vez unido, podrá enviar sus pronósticos.</p>

  <h1>4. Pronósticos de partidos</h1>

  <p>Cada usuario puede introducir un pronóstico para cada partido:</p>

  <ul>
    <li>goles del equipo local</li>
    <li>goles del equipo visitante</li>
  </ul>

  <p>Los pronósticos solo pueden modificarse antes del inicio del partido.</p>
  <p>Una vez comenzado el partido, el pronóstico queda bloqueado.</p>

  <h1>5. Pronósticos de campeones</h1>

  <p>Los usuarios pueden pronosticar:</p>

  <ul>
    <li>campeones de grupo</li>
    <li>campeón final del torneo</li>
  </ul>

  <p>Estos pronósticos otorgan puntos adicionales según la configuración de la porra.</p>
  <p>Los pronósticos de campeones de otros participantes solo pueden consultarse cuando la competición ha comenzado.</p>


  <h1>6. Clasificación</h1>

  <p>La clasificación muestra los participantes ordenados por puntos.
    Los puntos se calculan automáticamente en función de:

    aciertos en partidos
    aciertos de campeones

    La clasificación se actualiza cuando se registran resultados reales.</p>

  <h1>7. Resultados y actualización automática</h1>

  <p>La aplicación obtiene los resultados reales de los partidos mediante una API externa.</p>
  <p>Los resultados se utilizan para:</p>
  <ul>
    <li>calcular los puntos</li>
    <li>actualizar la clasificación</li>
  </ul>
  <p>En caso necesario, los resultados pueden introducirse manualmente para pruebas.</p>

  <h1>8. Consideraciones finales</h1>

  <p>La aplicación controla el acceso a la información para garantizar la equidad.</p>
  <p>El usuario siempre recibe mensajes informativos cuando una acción no está permitida.</p>
  <p>El sistema está diseñado para ser intuitivo y fácil de usar.</p>

</div>
@endsection