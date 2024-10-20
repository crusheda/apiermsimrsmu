<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Cek Surat Kontrol</title>
    <link rel="apple-touch-icon" sizes="76x76" href="{{ asset('img/pku/pku_ico.png') }}">
    <link rel="icon" type="image/png" sizes="96x96" href="{{ asset('img/pku/pku_ico.png') }}">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/v/bs5/jszip-3.10.1/dt-2.1.8/b-3.1.2/b-colvis-3.1.2/b-html5-3.1.2/b-print-3.1.2/cr-2.0.4/r-3.0.3/datatables.min.css" rel="stylesheet">
    {{-- <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css" rel="stylesheet"> --}}
</head>
<body>
    <div class="container p-10" style="max-width: 1900px">
        <div class="row">
            <div class="col-md-12" style="display:flex; position:absolute; top:0; bottom:0; right:0; left:0;">
                <div class="table-responsive" style="margin:auto;">
                    <h2 class="text-primary"><center><a href="javascript:void(0);" onclick="refresh()"><b>Data Surat Kontrol</b></a></center></h2>
                    <h5><center><b>Rumah Sakit PKU Muhammadiyah Sukoharjo</b></center></h5>
                    <div id="loading"><center><i class="fa fa-spinner fa-spin fa-fw"></i> Memproses data...</center></div>
                    <table id="dt-table" class="table table-bordered table-hover" style="width:100%" hidden>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>KUNJUNGAN<br>DAN NOPEN</th>
                                <th>NO. REFERENSI<br>SURAT KONTROL</th>
                                <th>BPJS PESERTA</th>
                                <th>NORM - NAMA PASIEN<br>ALAMAT</th>
                                <th>POLIKLINIK DOKTER SPESIALIS</th>
                                <th>RENCANA KONTROL</th>
                                <th>KETERANGAN</th>
                                <th>DIBUAT TANGGAL</th>
                                <th>DIBUAT OLEH</th>
                            </tr>
                        </thead>
                        <tbody id="tampil-tbody">
                            <tr>
                                <td colspan="20">
                                    <center><i class="fa fa-spinner fa-spin fa-fw"></i> Memproses data...</center>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</body>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
<script defer src="https://use.fontawesome.com/releases/v5.15.4/js/all.js" integrity="sha384-rOA1PnstxnOBLzCLMcre8ybwbTmemjzdNlILg8O7z1lUkLXozs4DHonlDtnE7fpc" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>
<script src="https://cdn.datatables.net/v/bs5/jszip-3.10.1/dt-2.1.8/b-3.1.2/b-colvis-3.1.2/b-html5-3.1.2/b-print-3.1.2/cr-2.0.4/r-3.0.3/datatables.min.js"></script>
<script>
    $(document).ready( function () {
        refresh();
    })

    function refresh() {
        $("#tampil-tbody").empty().append(
            `<tr><td colspan="20"><center><i class="fa fa-spinner fa-spin fa-fw"></i> Memproses data...</center></td></tr>`
        );
        $.ajax({
            url: "/api/surkon/table",
            type: 'GET',
            dataType: 'json',
            success: function(res) {
                $("#tampil-tbody").empty();
                $('#dt-table').DataTable().clear().destroy();
                res.show.forEach(item => {
                    var content = ``;
                    content += `<tr>`;
                    content += `<td>${item.ID}</td>`;
                    content += `<td>${item.KUNJUNGAN}<br>${item.NOPEN}</td>`;
                    content += `<td>${item.NOMOR_REFERENSI}</td>`;
                    content += `<td>${item.NOBPJS}</td>`;
                    content += `<td>(<b>${item.NORM}</b>) ${item.NMPASIEN}<br><sub>${item.ALPASIEN}</sub></td>`;
                    content += `<td>${item.NMRUANGAN} - ${item.NMDOKTER}</td>`;
                    content += `<td>${item.TANGGAL} ${item.JAM}</td>`;
                    content += `<td>${item.DEKRIPSI?item.DEKRIPSI:''}</td>`;
                    content += `<td>${item.DIBUAT_TANGGAL}</td>`;
                    content += `<td>${item.USER}</td>`;
                    content += `</td>`;
                    $('#tampil-tbody').append(content);
                })
                $("#loading").prop('hidden', true);
                $("#dt-table").prop('hidden', false);
                new DataTable('#dt-table', {
                    dom: 'Bfrtip',
                    order: [
                        [8, "desc"]
                    ],
                    bAutoWidth: false,
                    aoColumns : [
                        { sWidth: '4%' },
                        { sWidth: '10%' },
                        { sWidth: '10%' },
                        { sWidth: '8%' },
                        { sWidth: '20%' },
                        { sWidth: '15%' },
                        { sWidth: '5%' },
                        { sWidth: '13%' },
                        { sWidth: '5%' },
                        { sWidth: '10%' },
                    ],
                    displayLength: 10,
                    lengthChange: true,
                    lengthMenu: [ 10, 25, 50, 75, 100, 500, 1000, 5000, 10000],
                    buttons: ['excel', 'pdf', 'colvis']
                });
            },
            error: function(res) {

            }
        })
    }
</script>
</html>
