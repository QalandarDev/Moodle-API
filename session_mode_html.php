<?php

$html = '<html>
 <head>
    <title>@UrDUMoodleBot |  Brouzer sessiyalari</title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta name="keywords" content="@UrDUMoodleBot,HTML Browser sessions" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
  <body><h2>@UrDUMoodleBot | HTML export sessions</h2>
    <table class="generaltable">
<thead>
<tr>
<th class="header c0" style="text-align:left;" scope="col">Log in</th>
<th class="header c1" style="text-align:left;" scope="col">Last access</th>
<th class="header c2" style="text-align:left;" scope="col">Last IP address</th>
<th class="header c3 lastcol" style="text-align:right;" scope="col">ID</th>
</tr>
</thead><tbody>';
$lines = file("sessions.txt");
$data = array();
foreach ($lines as $line) {
    $data[] = explode(';', trim($line));
}

foreach ($data as $td) {
    $html .= '<tr class="">
<td class="cell c0" style="text-align:left;">' . $td[0] . '</td>
<td class="cell c1" style="text-align:left;">' . $td[1] . '</td>
<td class="cell c2" style="text-align:left;">' . $td[2] . '</td>
<td class="cell c3 lastcol" style="text-align:right;">' . $td[3] . '</td>
</tr>';
}
$html .= '</tbody></table>
        <style>
          .generaltable {
 width:100%;
 margin-bottom:1rem;
 color:#343a40;
}
.generaltable th,
.generaltable td {
 padding:.75rem;
 vertical-align:top;
 border-top:1px solid #dee2e6;
}
.generaltable thead th {
 vertical-align:bottom;
 border-bottom:2px solid #dee2e6
}
.generaltable tbody+tbody {
 border-top:2px solid #dee2e6
}
.generaltable tbody tr:nth-of-type(odd) {
 background-color:rgba(0,0,0,.05);
}
.generaltable.table-sm th,
.generaltable.table-sm td {
 padding:.3rem
}
.generaltable tbody tr:hover {
 color:#343a40;
 background-color:rgba(0,0,0,.075)
}
table caption {
 font-size:24px;
 font-weight:700;
 line-height:42px;
 text-align:left;
 caption-side:top
}
.table-dynamic .loading-icon {
 position:absolute;
 left:calc(50% - 1.5rem);
 top:200px
}
.table-dynamic .loading-icon .icon {
 height:3rem;
 width:3rem;
 font-size:3rem
}
        </style>
  </body>
  </html>';
echo $html;
