<?php

declare(strict_types=1);

namespace Modules\Academic\Infrastructure\Services;

final class PilotNormativeAlignment
{
    public const VERIFIED_ON = '17 de septiembre de 2026';

    /**
     * This is an evidence index, not a legal opinion or an institutional approval.
     * Each row deliberately separates the official expectation from EduDrive evidence
     * and from the external decision that is still required.
     *
     * @return array<int, array<string, string>>
     */
    public function rows(): array
    {
        return [
            [
                'source' => 'Ley 9078 · artículo 217 vigente',
                'expectation' => 'La educación vial es obligatoria y debe promover convivencia responsable entre peatones, ciclistas, pasajeros y conductores, buenas prácticas y atención a las necesidades de las personas con discapacidad.',
                'evidence' => 'La unidad P912 trabaja los roles de peatón y pasajero, la espera protegida, la observación, el cambio de plan y la ayuda adulta.',
                'status' => 'Correspondencia parcial',
                'gap' => 'El MEP debe determinar ubicación curricular, profundidad, secuencia y pertinencia por edad; DGEV y COSEVI deben aportar revisión técnica.',
            ],
            [
                'source' => 'Ley 9078 · artículo 120',
                'expectation' => 'Las personas peatonas deben usar aceras y cruzar por esquinas, pasos marcados o pasos a desnivel; si no existe espacio peatonal, la norma define una conducta excepcional.',
                'evidence' => 'Las escenas enseñan a permanecer en la acera, identificar el paso peatonal y evitar iniciar una maniobra con información insuficiente.',
                'status' => 'Correspondencia parcial',
                'gap' => 'Cada geometría, trayectoria, señal y desenlace visual debe recibir dictamen de seguridad vial por versión.',
            ],
            [
                'source' => 'Ley 9078 · semáforos y prioridad peatonal',
                'expectation' => 'La luz verde habilita la marcha, pero quien conduce debe ceder a peatones y ciclistas presentes en la calzada, incluidos los giros permitidos.',
                'evidence' => 'La situación del vehículo que gira mantiene la obligación de la persona conductora y enseña al niño a comprobar el conflicto sin trasladarle esa responsabilidad.',
                'status' => 'Correspondencia parcial',
                'gap' => 'Validar fases semafóricas, carriles, giros, velocidades y tiempos antes de usar la escena como material oficial.',
            ],
            [
                'source' => 'Ley 9078 · seguridad de pasajeros',
                'expectation' => 'La conducción debe proteger a las personas pasajeras y cumplir las medidas de seguridad establecidas.',
                'evidence' => 'La escena de descenso de la van practica esperar, bajar hacia un espacio protegido y recuperar visibilidad antes de decidir.',
                'status' => 'Cobertura inicial',
                'gap' => 'Ampliar progresivamente a cinturones, dispositivos infantiles y transporte público según edad y revisión especializada.',
            ],
            [
                'source' => 'Ley 9078 · artículo 218 y Decreto 42111',
                'expectation' => 'COSEVI desarrolla campañas y las instituciones coordinan acciones formativas que promuevan hábitos, habilidades, actitudes y prácticas para los distintos roles viales.',
                'evidence' => 'EduDrive propone práctica de decisiones, retroalimentación, transferencia protegida y seguimiento, no solo memorización de señales.',
                'status' => 'Compatible en intención',
                'gap' => 'Solicitar revisión y una vía formal de cooperación. EduDrive no es una campaña de COSEVI ni cuenta con su aval.',
            ],
            [
                'source' => 'Ley 7600 y reglamento',
                'expectation' => 'Los servicios deben garantizar igualdad de oportunidades, accesibilidad, participación y no discriminación de las personas con discapacidad.',
                'evidence' => 'Las escenas incluyen alternativa textual y el proceso exige revisión de accesibilidad independiente.',
                'status' => 'Diseño preliminar',
                'gap' => 'Realizar pruebas con tecnologías de apoyo y personas usuarias; documentar ajustes razonables y equivalencia de las tareas.',
            ],
            [
                'source' => 'Leyes 8968 y 10238',
                'expectation' => 'El tratamiento de datos personales requiere garantías; la difusión o uso identificable de imagen, voz o datos de menores exige especial protección y consentimiento de responsables legales.',
                'evidence' => 'Los instrumentos actuales no se almacenan, usan códigos de sesión y excluyen grabación, nombre, imagen y voz por defecto.',
                'status' => 'Control preventivo',
                'gap' => 'Antes de un piloto real: dictamen jurídico, base habilitante, roles de tratamiento, conservación, acceso, incidentes y autorizaciones institucionales.',
            ],
        ];
    }

    /** @return array<int, array<string, string>> */
    public function sources(): array
    {
        return [
            ['label' => 'Ley de Tránsito por Vías Públicas Terrestres y Seguridad Vial, N.° 9078', 'institution' => 'Sistema Costarricense de Información Jurídica · PGR', 'url' => 'https://pgrweb.go.cr/Scij/Busqueda/Normativa/Normas/nrm_texto_completo.aspx?nValor1=1&nValor2=73504&param1=NRM&strTipM=FN'],
            ['label' => 'Artículo 217 vigente y nota de reforma futura', 'institution' => 'Sistema Costarricense de Información Jurídica · PGR', 'url' => 'https://www.pgrweb.go.cr/DOCS/NORMAS/1/VIGENTE/L/2010-2019/2010-2014/2012/11F20/12B92E.HTML'],
            ['label' => 'Reglamento de la Ley de Movilidad y Seguridad Ciclística, Decreto 42111', 'institution' => 'Sistema Costarricense de Información Jurídica · PGR', 'url' => 'https://pgrweb.go.cr/scij/Busqueda/Normativa/Normas/nrm_texto_completo.aspx?lResultado=2&nValor1=1&nValor2=90312&nValor3=118861&nValor4=1&param1=NRTC&param2=1&strSelect=sel&strTipM=TC'],
            ['label' => 'Ley de Protección de la Persona frente al tratamiento de sus datos personales, N.° 8968', 'institution' => 'Sistema Costarricense de Información Jurídica · PGR', 'url' => 'https://www.pgrweb.go.cr/DOCS/NORMAS/1/VIGENTE/L/2010-2019/2010-2014/2011/1153F/DCEF7.HTML'],
            ['label' => 'Protección de imagen, voz y datos de personas menores de edad, Ley N.° 10238', 'institution' => 'Sistema Costarricense de Información Jurídica · PGR', 'url' => 'https://www.pgrweb.go.cr/DOCS/NORMAS/1/VIGENTE/L/2020-2029/2020-2024/2022/17CE2/151A54.HTML'],
            ['label' => 'Reglamento de la Ley 7600 sobre igualdad de oportunidades', 'institution' => 'Sistema Costarricense de Información Jurídica · PGR', 'url' => 'https://www.pgrweb.go.cr/DOCS/NORMAS/1/VIGENTE/D/1990-1999/1995-1999/1998/CFA8/75808.HTML'],
            ['label' => 'Programas de estudio', 'institution' => 'Ministerio de Educación Pública', 'url' => 'https://mep.go.cr/programas-estudio'],
            ['label' => 'Programa Camino Seguro', 'institution' => 'Ministerio de Educación Pública', 'url' => 'https://www.mep.go.cr/programas-proyectos/camino-seguro'],
        ];
    }
}
