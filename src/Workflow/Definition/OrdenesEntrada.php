<?php

$definiciones =
  [
    "Entradas" =>
      [
        "Descripcion" => "Ordenes de entrada para el ingreso de mercancias de un contenedor",
        "Acciones" =>
          [
            'Finalizar',
            'Rechazar'
          ],
        "Estados" =>
          [
            'En captura',
            'En proceso',
            'Finalizado'
          ],
        "EstadoInicial" => "En captura"
        "EstadosFinales" => "Finalizado"
        "Transiciones" =>
          [
            ["Origen"=>"En captura", "Transicion"=>"Finalizar", "Final"=>"En proceso"],
            ["Origen"=>"En proceso", "Transicion"=>"Finalizar", "Final"=>"Finalizado"]
          ]
      ],
    "Salidas" =>
    [
      "Descripcion" => "Ordenes de salida para el egreso de mercancias de un contenedor",
      "Acciones" =>
        [
          'Finalizar',
          'Rechazar'
        ],
      "Estados" =>
        [
          'En captura',
          'En proceso',
          'Finalizado'
        ],
      "EstadoInicial" => "En captura"
      "EstadosFinales" => "Finalizado"
      "Transiciones" =>
        [
          ["Origen"=>"En captura", "Transicion"=>"Finalizar", "Final"=>"En proceso"],
          ["Origen"=>"En proceso", "Transicion"=>"Finalizar", "Final"=>"Finalizado"]
        ]
    ]
  ];

 ?>
