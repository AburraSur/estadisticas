
$(document).ready(function(){
    $('#dateAgenda').datepicker({
        dateFormat: 'yy-mm-dd',
        language: 'es',
        numberOfMonths: 2,
        onSelect: function() {
            var date = $(this).val();
            alert(date);
        }
    });
});
////var count = 0;
//var update_Insert = '';
//var day = '';
//var typeAction = 'normal';
//var salaNombre = '';
//var changeStudy = false;
//var RoomName = '';
//var RoomId = '';
//
//$(document).ready(function() {
//
//    /**
//     *
//     * Para consultar los bloqueos en espacio de agenda por sala.
//     * El id es para consultar los bloqueos de esa sala.
//     * Name es para mostrar en la vista el nombre de la sala.
//     *
//     */
//    $(document).on('click', '.salas', function () {
//        RoomName = $(this).html();
//        RoomId = $(this).val();
//    });
//
//    $('#btn-print-ind').hide();
//$('.selectpicker').selectpicker();
//$('.selectpicker2').selectpicker();
//$('.selectpicker3').selectpicker();
//
//
//    var datemin=new Date();
//    $("#input_fechaDeseada").datepicker({
//        dateFormat: 'yy-mm-dd',
//        changeMonth: true,
//        changeYear: true,
//        showButtonPanel: true,
//        yearRange: "-1:+1"
//    });
//    $("#input_fechaDeseada").datepicker('option', 'minDate', datemin);
//
//
//   $('#tags').keyup(function (e) {
//        if (e.keyCode == 13) {
//            $( "#searchPatientList" ).trigger( "click" );
//            e.preventDefault();
//            return false;
//        }
//    });
//
//
//	$('#searchPatientList').click(function () {
//
//        $.blockUI({
//            css: {
//                border: 'none',
//                padding: '15px',
//                '-webkit-border-radius': '10px',
//                '-moz-border-radius': '10px',
//                'border-radius': '10px',
//                color: '#fff'
//
//            },
//            message: '<h3><img style="width: 40px;" src="' + SrcGif + '" style="border: none;"/> '+wait+'...',
//            baseZ: 99999
//        });
//
//        var valor = $("#tags").val();
//        if ($.trim(valor) != "") {
//            changeDataDocument(valor);
//        } else {
//            $('#tags').addClass('error');
//            setTimeout($.unblockUI, 2500);
//        }
//
//    });
//
//    $('.selectpicker').change(function(){
//        $.blockUI({ css: {
//                border: 'none',
//                padding: '15px',
//                '-webkit-border-radius': '10px',
//                '-moz-border-radius': '10px',
//                'border-radius': '10px',
//                color: '#fff'
//
//            },message:'<h3><img style="width: 40px;" src="'+SrcGif+'" style="border: none;"/> '+wait+'...' , baseZ: 99999 });
//        var sede = $(this).val();
//
//        $.ajax({
//            url: UrlSalas,
//            type: 'POST',
//            data: {'sede':sede },
//            success: function(data){
//                var resp = JSON.parse(data);
//                console.log(data);
//                $.unblockUI();
//                $('.tabs').html(resp.salas);
//
//                $('.salas').click(function(){
//                    var sala = $(this).attr('value');
//                    var selSalaMod = $(this).data('mod');
//                    var selSalaHq = $(this).data('hq');
//
//                    var selectMod = $('#modSala').val();
//                    var selectHq = $('#hqSala').val();
//                    if((update_Insert == true)&&((selSalaMod != selectMod)||(selSalaHq != selectHq))){
//                        $.blockUI({ css: {
//                            border: 'none',
//                            padding: '15px',
//                            '-webkit-border-radius': '10px',
//                            '-moz-border-radius': '10px',
//                            'border-radius': '10px',
//                            color: '#fff'
//
//                        },message:'<h4><span class="glyphicon glyphicon-alert text-danger" style="font-size:40px;" ></span></h4><h4> '+mismaModalidad+'</h4><a href="#" id="clch" class="btn btn-danger2" >'+acepta+'</a></div>', baseZ: 99999 });
//                        $('#clch').click(function(){
//                            $.unblockUI();
//                        });
//                    }else{
//                    $('#modSala').val(selSalaMod);
//                    $('#hqSala').val(selSalaHq);
//                    var dateSearch = $('#date-search').val();
//                    var idSche = $('#idSchedule').val();
//                    $('#idUser').prop('value','');
//                    var user = $('#idUser').val();
//
//                            if(idSche !=''){
//
//                            $.blockUI({ css: {
//                                    border: 'none',
//                                    padding: '15px',
//                                    '-webkit-border-radius': '10px',
//                                    '-moz-border-radius': '10px',
//                                    'border-radius': '10px',
//                                    color: '#fff'
//
//                                },message:'<h3>'+liberarEspacios+'</h3><br><center><button class="btn btn-primary" id="acept" >'+acepta+'</button><button class="btn btn-primary" id="cancel" >'+cancela+'</button></center>' });
//                            $('#acept').click(function(){
//                                setTimeout($.unblockUI, 200);
//                                $('#multiCita').hide('fast');
//                                $.blockUI({ css: {
//                                    border: 'none',
//                                    padding: '15px',
//                                    '-webkit-border-radius': '10px',
//                                    '-moz-border-radius': '10px',
//                                    'border-radius': '10px',
//                                    color: '#fff'
//
//                                },message:'<h3><img style="width: 40px;" src="'+SrcGif+'" style="border: none;"/> '+wait+'....<br>'+liberandoEspacios , baseZ: 99999 });
//                                getCancelReservation(idSche,sala,dateSearch,user);
//                            $('#idUser').prop('value','');
//                            $('#idUserUpdate').prop('value','');
//                            $('.btn-primary').removeClass('active');
//                            $(this).addClass('active');
//                            $('#room-search').prop('value',sala);
//                            $('#room-searchUpdate').prop('value',sala);
//                            $('#selectMulti').prop('checked',false);
//                            getDisponibility(sala,dateSearch,'');
//
//                            });
//
//                            $('#cancel').click(function(){
//                                setTimeout($.unblockUI, 200);
//                                $('#selectMulti').prop('checked',true);
//
//                            });
//
//                        }else{
//                            $('#multiCita').hide('fast');
//                            $('#idUser').prop('value','');
//                            $('#idUserUpdate').prop('value','');
//                            $('.btn-primary').removeClass('active');
//                            $(this).addClass('active');
//                            $('#room-search').prop('value',sala);
//                            $('#room-searchUpdate').prop('value',sala);
//                            getDisponibility(sala,dateSearch,'');
//                        }
//
//                    }
//
//
//                    //var user = $('#idUser').val();
//
//
//                });
//            }
//        })
//        return false;
//    });
//
//
//            $('#citas').submit(function(){
//    return false;
//});
//
//$('#addPatient').click(function(){
//    $('#nohis').show('slow');
//});
//
//
//
//    $('#second_number_2').keyup(function(e){
//
//        if(e.keyCode == 13)
//        {
//            var valor = $('#tags').val();
//            changeDataDocument(valor);
//            //$('#second_number_2').focus();
//            return false;
//
//        }
//
//    });
//
////Modificación de la cita
//
//$('#appModify').click(function(){
//    var contadorC = $('#ContCitas').val();
//    if(contadorC > 1){
//        $.blockUI({
//            message: $('#divgrowlUI'),
////            fadeIn: 700,
////            fadeOut: 700,
////            timeout: 6000,
//            showOverlay: false,
//            centerY: false,
//            css: {
//                width: '350px',
//                top: '70px',
//                left: '',
//                right: '10px',
//                border: 'none',
//                padding: '5px',
//                backgroundColor: '#000',
//                '-webkit-border-radius': '5px',
//                '-moz-border-radius': '5px',
//                opacity: .95,
//                color: '#fff'
//            }
//        });
//    }
//    $('#info-dateAppNew').css('display','block');
////    $('#info-dateApp').html($('#info-dateApp1').text());
////    $('#info-pat').html('<h4>'+$('#patientNow').text()+'</h4>');
//    var appCode = $('#appCode1').val();
//    $('#appCode').val(appCode);
//    var idScheduleSelect = $('#codeScheduleSelect1').val();
//    $('#idScheduleSelect').val(idScheduleSelect);
//    var idSMS = $('#CodScheduleSms1').val();
//    $('#idSMS').val(idSMS);
//    var CodScheduleSelect = $('#idScheduleSelect').val();
//    $('#CodScheduleSelect').val(CodScheduleSelect);
//    $('#modalinfo').modal('hide');
//    update_Insert = true;
//    var salaName = $('#tituloDisp').html();
//    var NomSala = salaName.split(":");
//    salaNombre = NomSala[1];
//    $('#nomSalaChange').val(salaNombre);
//});
//
//$('#cancelUpdate').click(function(){
//    $.unblockUI();
//    update_Insert = false;
//    changeStudy = false;
//    $('#citasUpdate').each(function(){
//        this.reset();
//    });
//    var idsc = $('#idScheduleUpdate').val();
//    if(idsc != ''){
//        var dateSearch = $('#date-search').val();
//        var idSche = $('#idScheduleUpdate').val();
//		var user = $('#idUser').val();
//                var sala = $('active').val();
//		getCancelReservation(idSche,sala,dateSearch,user);
//    }
//    $('#info-dateAppNew').css('display','none');
////    $('.select2-chosen').html('');
//    $('.selectpicker3').selectpicker('deselectAll');
//    //$('.alert').hide('fast');
//    $('#selectCountUpdate').prop('value', 1);
//    $("#appCode").prop('value','');
//    $('.limpiar').prop('value','');
//    var ch = $('#selectMulti').prop('checked');
//    if (ch) {
//        $('#selectMulti').prop('checked', false);
//        $('#multiCita').hide('fast');
//        $('#idSchedule').prop('value','');
//        $('#idInitUpdate').prop('value', '');
//        $('#idEndUpdate').prop('value', '');
//        $("#appCode").prop('value','');
//    }
//});
//
//$('#hour_new').timepicker();
//
//var dateMin=new Date();
//$("#date_new").datepicker({
//    onSelect: function(date){
//        $("#date_new").datepicker('option', 'minDate', date);
//    },
//    onload: function(date){
//        $("#date_new").datepicker('option', 'minDate', date);
//    },
//    dateFormat: 'yy-mm-dd',
//    changeMonth: true,
//    changeYear: true,
//    showButtonPanel: true,
//    yearRange: "-1:+1"
//});
//
//$('#tabs').removeClass('ui-widget-content');
////$('#modal').modal('show');
////$('.basic').select2();
//$('.tabRoom').click(function(){
//    $('#div-dispUser').hide('fast').html('');
//    $('#div-dispRoom').show('fast');
//     var sala = $('#room-search').val();
//    var dateSearch = $('#date-search').val();
//    
//    getDisponibility(sala,dateSearch,'');
//});
//$('.tabUser').click(function(){
//    $('#div-dispRoom').hide('fast').html('');
//    $('.table-dispon').remove();
//    $('#div-dispUser').show('fast');
//});
//$( "#calendar" ).datepicker({
//        onSelect: function( date ){
//            $.blockUI({
//                css: {
//                    border: 'none',
//                    padding: '15px',
//                    '-webkit-border-radius': '10px',
//                    '-moz-border-radius': '10px'
//
//                },
//                message:'<h3><img style="width: 40px;" src="'+SrcGif+'" style="border: none;"/>'+cargando+'...' ,
//                baseZ: 99999
//            });
//            var salach = '';
//            if(update_Insert == true){
//                salach = $('#room-searchUpdate').val();
//            }else if(update_Insert == false){
//                salach = $('#room-search').val();
//            }
//            //var dateSearch = $('#date-search').val();
//            //var user = $('#idUser').val();
//            if(salach == ''){
//                setTimeout($.unblockUI,1500);
//            }
//            var idScheRu = $('#idScheRu').val();
//            $('#date-search').prop('value',date);
//            $('#date-searchUpdate').prop('value',date);
//            if(salach !=''){
//                var dateSearch = '';
//                var user = '';
//                if(update_Insert == true) {
//                    dateSearch = $('#date-searchUpdate').val();
//                    user = $('#idUserUpdate').val();
//                }else if(update_Insert == false){
//                    dateSearch = $('#date-search').val();
//                    user = $('#idUser').val();
//                }
//                if(user!=''){
//                    getDisponibilityUser(salach,dateSearch,user,idScheRu,day);
//                }else{
//                    getDisponibility(salach,dateSearch,user);
//                }
//            }
//
//    },
//    dateFormat: 'yy-mm-dd',
//      numberOfMonths: 2,
//});
//
//
//
//$('.salas').click(function(){
//    var sala = $(this).attr('value');
//    var selSalaMod = $(this).data('mod');
//    var selSalaHq = $(this).data('hq');
//
//    var selectMod = $('#modSala').val();
//    var selectHq = $('#hqSala').val();
//    if((update_Insert == true)&&((selSalaMod != selectMod)||(selSalaHq != selectHq))){
//        $.blockUI({ css: {
//            border: 'none',
//            padding: '15px',
//            '-webkit-border-radius': '10px',
//            '-moz-border-radius': '10px',
//            'border-radius': '10px',
//            color: '#fff'
//
//        },message:' <h4><span class="glyphicon glyphicon-alert text-danger" style="font-size:40px;" ></span></h4><h4> '+mismaModalidad+'</h4><a href="#" id="clch" class="btn btn-danger2" >'+acepta+'</a>', baseZ: 99999 });
//        $('#clch').click(function(){
//            $.unblockUI();
//        });
//    }else{
//        $('#modSala').val(selSalaMod);
//        $('#hqSala').val(selSalaHq);
//        var dateSearch = $('#date-search').val();
//        var idSche = $('#idSchedule').val();
//        var user = $('#idUser').val();
//
//                if(idSche !=''){
//
//                $.blockUI({ css: {
//                        border: 'none',
//                        padding: '15px',
//                        '-webkit-border-radius': '10px',
//                        '-moz-border-radius': '10px',
//                        'border-radius': '10px',
//                        color: '#fff'
//
//                    },message:'<h3>'+liberarEspacios+'</h3><br><center><button class="btn btn-primary" id="acept" >'+acepta+'</button><button class="btn btn-primary" id="cancel" >'+cancela+'</button></center>' });
//                $('#acept').click(function(){
//                    setTimeout($.unblockUI, 200);
//                    $('#multiCita').hide('fast');
//                    getCancelReservation(idSche,sala,dateSearch,user);
//                $('#idUser').prop('value','');
//                $('#idUserUpdate').prop('value','');
//                $('.btn-primary').removeClass('active');
//                $(this).addClass('active');
//                $('#room-search').prop('value',sala);
//                $('#room-searchUpdate').prop('value',sala);
//                $('#selectMulti').prop('checked',false);
//                getDisponibility(sala,dateSearch,'');
//
//                });
//
//                $('#cancel').click(function(){
//                    setTimeout($.unblockUI, 200);
//                    $('#selectMulti').prop('checked',true);
//
//                });
//
//            }else{
//                $('#multiCita').hide('fast');
//                $('#idUser').prop('value','');
//                $('#idUserUpdate').prop('value','');
//                $('.btn-primary').removeClass('active');
//                $(this).addClass('active');
//                $('#room-search').prop('value',sala);
//                $('#room-searchUpdate').prop('value',sala);
//                $.blockUI({ css: {
//                    border: 'none',
//                    padding: '15px',
//                    '-webkit-border-radius': '10px',
//                    '-moz-border-radius': '10px',
//                    'border-radius': '10px',
//                    color: '#fff'
//
//                },message:'<h3><img style="width: 40px;" src="'+SrcGif+'" style="border: none;"/>'+cargando+'...' , baseZ: 99999 });
//                getDisponibility(sala,dateSearch,'');
//            }
//
//        }
//    //var user = $('#idUser').val();
//
//
//});
//
//$('#btn-update').on('click', function(){
//    var appCode = $('#appCode').val();
//    var valbtn = $('#timeDateUpdate').val();
//    var valbtn2 = $('#date-searchUpdate').val();
//    var sala = $('#room-searchUpdate').val();
//    var idScheRu = $('#idScheRu').val();
//    var user = $('#idUserUpdate').val();
//    var idMod = $('#idModalityUpdate').val();
//
//    $.blockUI({ css: {
//                    border: 'none',
//                    padding: '15px',
//                    '-webkit-border-radius': '10px',
//                    '-moz-border-radius': '10px',
//                    'border-radius': '10px',
//                    color: '#fff'
//
//                } ,message: '<h3>'+seguro+'</h3><br>\n\
//                                 <textarea name="commentUpdate" id="commentUpdate" placeholder="'+motivoModif+'" style="width: 90%;" rows="4" ></textarea>\n\
//                                 <br style="heigth:20px" >\n\
//                                 <center><button class="btn btn-primary" id="acept4" style="margin:20px 5px; " value="MODIFICACION" >'+si+'</button>\n\
//                                 <button class="btn btn-primary" id="cancel4" >'+no+'</button></center>',
//                    baseZ: 99999
//                });
//    $('#acept4').click(function () {
//
//        var appCode = $('#appCode').val();
//        var idScheduleUpdate = $('#idScheduleUpdate').val();
//        var comment = $('#commentUpdate').prop('value');
//        var action = $(this).prop('value');
//
//        if (user != '') {
//            getDisponibilityUser(sala, valbtn2, user, idScheRu,day);
//        } else {
//            getDisponibility(sala, valbtn2, user);
//        }
//
//        if (comment == '') {
//            $('#commentCancel').addClass('error');
//        } else {
//            $.blockUI({ css: {
//                        border: 'none',
//                        padding: '15px',
//                        '-webkit-border-radius': '10px',
//                        '-moz-border-radius': '10px',
//                        'border-radius': '10px',
//                        color: '#fff'
//
//                    },message:'<h3>'+modCita+'<br><img style="width: 40px;" src="'+SrcGif+'" style="border: none;"/> '+wait+'......' , baseZ: 99999 });
//            $.ajax({
//                url: UrlScheduleOption,
//                type: 'POST',
//                data: {'idSche': idScheduleUpdate,'descrip': comment ,'action': action,'appCode':appCode},
//                success: function (data) {
//                    $.ajax({
//                        url: UrlUpdateData,
//                        type: 'POST',
//                        data: $('#citasUpdate').serialize(),
//                        success: function (data) {
//                            var obj = JSON.parse(data);
//                            console.log(data);
//                            $('#modalUpdate').modal('toggle');
//                            //$('#divsuccess').toggle('slow');
//                            if (user != '') {
//                                getDisponibilityUser(sala, valbtn2, user, idScheRu,day);
//                            } else {
//                                getDisponibility(sala, valbtn2, user);
//                            }
//
//                            $.blockUI({
//                                css: {
//                                    border: 'none',
//                                    padding: '15px',
//                                    '-webkit-border-radius': '10px',
//                                    '-moz-border-radius': '10px',
//                                    'border-radius': '10px',
//                                    color: '#fff'
//
//                                },
//                                message: '<div class="alert alert-success" role="alert" style="font-size:30px; margin:0; padding:0;" > <span class="glyphicon glyphicon-check" aria-hidden="true" style="margin-right:20px;" ></span>'+processOk+'</div>',
//                                baseZ: 99999
//                            });
//                            setTimeout(function () {
//                                $('#divmsn').toggle('slow');
//                                $('#citasUpdate').each(function () {
//                                    this.reset();
//                                });
//                                $('.limpiar').prop('value','');
//
////                                $('.select2-chosen').html('');
//                                $('.selectpicker3').selectpicker('deselectAll');
//                                //$('.alert').hide('fast');
//                                //$('#date-searchUpdate').prop('value', valbtn);
//                                $('#timeDateUpdate').prop('value', valbtn);
//                                $('#selectCountUpdate').prop('value', 1);
//                                $('#room-searchUpdate').prop('value', sala);
//                                $('#idModalityUpdate').prop('value', idMod);
//                                $("#appCode").prop('value','');
////                                var ch = $('#selectMulti').prop('checked');
////                                if (ch == 'checked') {
//                                    $('#selectMulti').prop('checked', false);
//                                    $('#multiCita').hide('fast');
//                                    $('#idSchedule').prop('value','');
//                                    $('#idInitUpdate').prop('value', '');
//                                    $('#idEndUpdate').prop('value', '');
//                                    $("#appCode").prop('value','');
////                                }
//                                $('.addSelect').remove();
//                                //$.unblockUI();
//                                update_Insert = false;
//                                $('#info-dateAppNew').css('display', 'none');
//                            }, 3000);
//                            //setTimeout($.unblockUI, 100);
//                        },
//                        onFailure: function () {
//                            //alert('Se ha producido un error');
//                            $('#divalert').toggle('slow');
//                            setTimeout(function () {
//                                $('#divalert').toggle('slow');
//                                $('#modalUpdate').modal('toggle');
//                            }, 3000);
//                        }
//                    })
//                }
//            });
//        }
//    });
//
//    $('#cancel4').click(function () {
//        setTimeout($.unblockUI, 200);
//    });
//});
//
//$('.btn-save').on('click',function(){
//    var conterror = 0;
//    $('.required').each(function(){
//        var valc = $(this).prop('value');
//        var ids = $(this).attr('data-idtxt');
//        var id = $(this).attr('id');
//
//        if(valc == ''){
//
//            //if(($(this).hasClass('selectpicker3')) || ($(this).hasClass('selectpickerAll')) ){
//            if(($(this).hasClass('selectpicker3')) || ($(this).hasClass('selectpickerAll')) ){
//                $('[data-id="'+id+'"]').removeClass('btn-primary').addClass('btn-danger');
//            }else{
//                if(($(this).hasClass('reqtxt')) ){
//                    $('[data-id="'+ids+'"]').removeClass('btn-primary').addClass('btn-danger');
//                }
//            }
//
//
//
//
//            $(this).addClass('error');
//            conterror = conterror+1;
//
//        }else{
//            $(this).removeClass('error');
//            $('[data-id="'+ids+'"]').removeClass('btn-danger').addClass('btn-primary');
//        }
//    });
////    alert('entra aca');
//    $('.error').change(function(){
//        if(($(this).hasClass('selectpicker3')) || ($(this).hasClass('selectpickerAll')) ){
//            //$('.select2-choice').css( "border", "red solid 1px" );
//            var ids = $(this).prop('id');
//            $('[data-id="'+ids+'"]').removeClass('btn-danger').addClass('btn-primary');
//        }else{
//            $(this).removeClass('error');
//
//        }
//        conterror = conterror-1;
//    });
//
//    if(conterror == 0){
//
//    //var $btn = $(this).button('loading');
//    var valbtn = $('#date-search').val();
//    var sala = $('#room-search').val();
//    var idSche = $('#idSchedule').val();
//    var idScheRu = $('#idScheRu').val();
//    var user = $('#idUser').val();
//    var idMod = $('#idModality').val();
//    var weight = $('#weight').val();
//
//    $.blockUI({ css: {
//                    border: 'none',
//                    padding: '15px',
//                    '-webkit-border-radius': '10px',
//                    '-moz-border-radius': '10px',
//                    'border-radius': '10px',
//                    color: '#fff'
//
//                },message:'<h3><img style="width: 40px;" src="'+SrcGif+'" style="border: none;"/> '+wait+'...' , baseZ: 99999 });
//    $.ajax({
//        url: UrlSaveData,
//        type: 'POST',
//        data: $('#citas').serialize(),
//        success: function( data ){
//            var obj = JSON.parse(data);
//            $('#PrintIndications').attr('href', '#');
//            $('#PrintIndications').hide();
//            //$('#divsuccess').toggle('slow');
//            //alert(obj);
//            if(obj==22){
//                $.blockUI({ css: {
//                    border: 'none',
//                    padding: '15px',
//                    '-webkit-border-radius': '10px',
//                    '-moz-border-radius': '10px',
//                    'border-radius': '10px',
//                    color: '#fff'
//
//                },message:'<div class="alert alert-danger" role="alert" style="font-size:30px; margin:0; padding:0;" > <span class="glyphicon glyphicon-alert" aria-hidden="true" style="margin-right:20px;" ></span> '+espacioReservado+'</div>', baseZ: 99999 });
//                setTimeout($.unblockUI, 3000);
//            }else{
//
//            $.blockUI({ css: {
//                    border: 'none',
//                    padding: '15px',
//                    '-webkit-border-radius': '10px',
//                    '-moz-border-radius': '10px',
//                    'border-radius': '10px',
//                    color: '#fff'
//
//                },message:'<div class="alert alert-success" role="alert" style="font-size:30px; margin:0; padding:0;" > <span class="glyphicon glyphicon-check" aria-hidden="true" style="margin-right:20px;" ></span>'+citaAsignadaOk+'</div>', baseZ: 99999 });
//            setTimeout(function(){
//                $('#divmsn').toggle('slow');
//                $('#modal').modal('toggle');
//                $('#citas').each(function(){
//                    this.reset();
//                });
//                $('.limpiar').prop('value','');
//                $('.divIndi').html('');
////                $('.select2-chosen').html('');
//                $('.selectpicker3').selectpicker('deselectAll');
//                $('.selectpickerAll').selectpicker('deselectAll');
//                //$('.alert').hide('fast');
//                $('#date-search').prop('value',valbtn);
//                $('#selectCount').prop('value',1);
//                $('#room-search').prop('value',sala);
//                $('#idModality').prop('value',idMod);
////                var ch = $('#selectMulti').prop('checked');
//                $('#idSchedule').prop('value','');
////                    if(ch){
//                        $('#selectMulti').prop('checked',false);
//                        $('#multiCita').hide('fast');
//                        $('#idInitUpdate').prop('value','');
//                        $('#idEndUpdate').prop('value','');
//                        $('#idInit').prop('value','');
//                        $('#idEnd').prop('value','');
////                    }
//                $('.addSelect').remove();
//                if(user != ''){
//                    getDisponibilityUser(sala,valbtn,user,idScheRu,day);
//                }else{
//                    getDisponibility(sala,valbtn,user);
//                }
//
//            },3000);
//            //setTimeout($.unblockUI, 100);
//            //end del if
//        }
//        },
//        onFailure: function () {
//            //alert('Se ha producido un error');
//             $('#divalert').toggle('slow');
//              setTimeout(function(){
//                $('#divalert').toggle('slow');
//                $('#modal').modal('toggle');
//            },3000);
//        }
//    })
//    //$btn.button('reset');
//    return false;
//    }
//});
//
//$('.btn-closed').click(function(){
//    var valbtn = $('#date-searchUpdate').val();
//    var sala = $('#room-searchUpdate').val();
//    var idSche = $('#idScheduleUpdate').val();
//    var user = $('#idUserUpdate').val();
//    var idMod =  $('#idModalityUpdate').prop('value');
//    $('#date-searchUpdate').prop('value',valbtn);
//    $('#selectCountUpdate').prop('value',1);
//    $('#room-searchUpdate').prop('value',sala);
//    $('#idModalityUpdate').prop('value',idMod);
//    $('#indicaciones').html('');
//    $('#contraindicaciones').html('');
//    $('#preparaciones').html('');
//    $('#btn-print-ind').hide();
////    $('.select2-chosen').html('');
//    $('.selectpicker3').selectpicker('deselectAll');
//    //$('.alert').hide('fast');4
//    var ch = $('#selectMulti').prop('checked');
//    if(ch){
//        $('#selectMulti').prop('checked',false);
//        $('#multiCita').hide('fast');
//        $('#idInitUpdate').prop('value','');
//        $('#idEndUpdate').prop('value','');
//
//    }
//    $('.addSelect').remove();
//    $('.error').each(function(){
//        $(this).removeClass('error');
//    });
//
//    $('#s2id_select_studies> a').css( "border", "#aaa solid 1px" );
//    /////////////////////////////
//    $('#nohis').hide('fast');
//
//
//
//    getCancelReservation(idSche,sala,valbtn,user);
//
//});
//
//$('#btn-close').click(function(){
//     var valbtn = $('#date-search').val();
//     var sala = $('#room-search').val();
//     var idSche = $('#idSchedule').val();
//     var user = $('#idUser').val();
//     var idMod =  $('#idModality').prop('value');
//     $('.selectpickerAll').selectpicker('deselectAll');
//     $('.selectpicker3').selectpicker('deselectAll');
//     $('.parrafo').html('');
//     $('.selectpickerAll').each(function(){
//         var id = $(this).attr('id');
//         $('[data-id="'+id+'"]').removeClass('btn-danger').addClass('btn-primary')
//     });
//     var id3 = $('.selectpicker3').attr('id');
//     $('[data-id="'+id3+'"]').removeClass('btn-danger').addClass('btn-primary')
//
//
//    //LIMPIAR FORMULARIO DEL MODAL
//    $('#citas').each(function(){
//        this.reset();
//    });
//
//    $('.selectpicker3').selectpicker('deselectAll');
////    $('.select2-chosen').html('');
//    //$('.alert').hide('fast');4
//    var ch = $('#selectMulti').prop('checked');
//    if(ch){
//        $('#selectMulti').prop('checked',false);
//        $('#multiCita').hide('fast');
//        $('#idInit').prop('value','');
//        $('#idEnd').prop('value','');
//    }
//    $('.addSelect').remove();
//    $('.error').each(function(){
//        $(this).removeClass('error');
//    });
//
//    $('#s2id_select_studies> a').css( "border", "#aaa solid 1px" );
//    /////////////////////////////
//    $('#nohis').hide('fast');
//
//    getCancelReservation(idSche,sala,valbtn,user);
//    $('#date-search').prop('value',valbtn);
//     $('#selectCount').prop('value',1);
//     $('#room-search').prop('value',sala);
//     $('#idModality').prop('value',idMod);
//     $('#indicaciones').html('');
//    $('#contraindicaciones').html('');
//    $('#preparaciones').html('');
//    $('#btn-print-ind').hide();
//
//});
//
//$('#close').click(function(){
//    $('#modalinfo').modal('hide');
//});
//
//
//$('#selectMulti').click(function(){
//    var ch = $(this).prop('checked');
//    var idSche = '';
//    var valbtn = '';
//    var sala = '';
//    var user = '';
//    if(ch){
//        $('#multiCita').show('fast');
//    }else{
//        if(update_Insert == true){
//            var user = $('#idUserUpdate').val();
//            idSche = $('#idScheduleUpdate').val();
//            valbtn = $('#date-searchUpdate').val();
//            sala = $('#room-searchUpdate').val();
//            user = $('#idUserUpdate').val();
//        }else if(update_Insert == false){
//            var user = $('#idUser').val();
//            idSche = $('#idSchedule').val();
//            valbtn = $('#date-search').val();
//            sala = $('#room-search').val();
//            user = $('#idUser').val();
//        }
//        if(idSche !=''){
//
//            $.blockUI({ css: {
//                    border: 'none',
//                    padding: '15px',
//                    '-webkit-border-radius': '10px',
//                    '-moz-border-radius': '10px',
//                    'border-radius': '10px',
//                    color: '#fff'
//
//                },message:'<h3>'+liberarEspacios+'</h3><br><center><button class="btn btn-primary" id="acept" >'+acepta+'</button><button class="btn btn-primary" id="cancel" >'+cancela+'</button></center>' });
//            $('#acept').click(function(){
//                setTimeout($.unblockUI, 200);
//                $('#multiCita').hide('fast');
//                getCancelReservation(idSche,sala,valbtn,user);
//            });
//
//            $('#cancel').click(function(){
//                setTimeout($.unblockUI, 200);
//                $('#selectMulti').prop('checked',true);
//
//            });
//        }else{
//            $('#multiCita').hide('fast');
//        }
//    }
//});
//
//$('#multiCita').click( function() {
//    if (update_Insert == true) {
//        var idSche = $('#CodScheduleSelect').val();
//        dataModalUpdate(idSche, 0);
//        $('#modalUpdate').modal('show')
//    } else if (update_Insert == false) {
//        //$('#tags').focus();
//        if ($('#idSchedule').val()) {
//            $('#modal').modal('show');
//
//            /*if (typeAction == 'normal') {
//                $('#btn-save').show('fast');
//                $('#btn-saveSpecial').hide('fast');
//            } else {
//                $('#btn-save').hide('fast');
//                $('#btn-saveSpecial').show('fast');
//            }*/
//
//            $(window).on('shown.bs.modal', function () {
//                $('#tags').focus();
//            });
//        } else {
//            $.blockUI({
//                css: {
//                    border: 'none',
//                    padding: '15px',
//                    '-webkit-border-radius': '10px',
//                    '-moz-border-radius': '10px',
//                    'border-radius': '10px',
//                    color: '#fff'
//                },
//                message: '<div class="alert alert-danger" role="alert" style="font-size: 22px; margin: 0; padding: 12px;" > <span class="glyphicon glyphicon-alert" aria-hidden="true"></span> <span class="sr-only">Error:</span>'+unEspacio+'</div>'
//            });
//            setTimeout($.unblockUI, 2500);
//        }
//    }
//});
//
//$('.basic').change(function(){
//    var opt = $(this).prop('id');
//    var idStudy = $('#'+opt+' option:selected').val();
//    $.ajax({
//        url: UrlStudyIndications,
//        type: 'POST',
//        data: {idStudy:idStudy},
//        success: function(data){
//            var obj = JSON.parse(data);
//            console.log(obj);
//            $('#btn-print-ind').show();
//            $('#indicaciones').append(obj.indicaciones);
//            $('#contraindicaciones').append(obj.contraindicaciones);
//            $('#preparaciones').append(obj.preparaciones);
//            $('#btn-print-ind').click(function(){
//                var printIndicaciones=obj.indicaciones;
//                var printContraindicaciones=obj.contraindicaciones;
//                var printPreparaciones=obj.preparaciones;
//            });
//        }
//    });
//    return false;
//    //alert(opt2);
//});
//
//
//function getStudies(mod, count) {
//
//    $.ajax({
//        url: UrlDataStudies,
//        type: "POST",
//        data: {
//            'mod': mod
//        },
//        success: function (resp) {
//
//            var jsonResp = JSON.parse(resp);
//            console.info(resp);
////            for (var i = 0; i < jsonResp.length; i++) {
////                $(".selectpicker3").append('<option value="' + jsonResp[i].idStudyList + '">' + jsonResp[i].nameStudy + '</option>');
////            }
//            if(changeStudy == true){
//                var idSel = 'selStudy2' ;
//                var req = 'required2';
//            }else{
//                var idSel = 'selStudy1';
//                var req = 'required';
//            }
//
//            var selMun = '<select id="'+idSel+'" class="form-control '+req+'" name="'+idSel+'" data-id="'+idSel+'" data-style="btn-primary" data-live-search="true" title="'+select+'" multiple >';
//            $('#'+idSel).html();
//            $('#div'+idSel).html();
//            $('#pselStudy1').html('');
//            for (var i = 0; i < jsonResp.length; i++) {
//                //$("#municipalityName").append('<option value="' + jsonResp[i].municipalityCode + '">' + jsonResp[i].municipalityName + '</option>');
//                selMun = selMun + '<option value="'+jsonResp[i].idStudyList+'">'+jsonResp[i].nameStudy+'</option>';
//            }
//            selMun = selMun + '</select><input type="hidden" name="txt'+idSel+'" id="txt'+idSel+'" />';
//            //$("#municipalityName option[value='" + munCode + "']").prop("selected", true);
//            $('#div'+idSel).html(selMun);
//
//            $('#' + idSel).addClass('selectpicker3').selectpicker();
//            $('.selectpicker3').change( function () {
//                var val = $(this).val();
//                var id = $(this).attr('data-id');
//                var dicID = 'div' + id;
//                $('#txt' + id).val(val);
//                var texto = $('#' + dicID + ' button.dropdown-toggle').attr('title');
//                $('#p' + id).html(texto);
//                $.ajax({
//                    url: UrlStudyIndications,
//                    type: 'POST',
//                    data: {idStudy: val},
//                    success: function (data) {
//                        var obj = JSON.parse(data);
//
//                        console.log(obj);
//
//                        $('#indicaciones').html(obj.indicaciones);
//                        $('#contraindicaciones').html(obj.contraindicaciones);
//                        $('#preparaciones').html(obj.preparaciones);
//                        var date = $('#fechaasig').html(); // Fecha de la cita
//                        var study = $('#pselStudy1').html(); // Estudio
//                        var user = $('#tags').val(); // Nombres del paciente
//                        var i = $('#selStudy1').val();
//                        var codeSchedule = $('#idSchedule').val();
//
//                        if (obj.indicaciones || obj.contraindicaciones || obj.preparaciones != '') {
//                            $('#PrintIndications').show('slow').attr('href', PDFPrint + '?date=' + date + '&study=' + study + '&user=' + user + '&idRoom=' + idRoom + '&idStudy=' + i + '&codeSchedule=' + codeSchedule);
//                        }
//                    }
//                });
//                return false;
//            });
//
//
//
//        },
//        onFailure: function () {
//            $.blockUI({ 
//                css: {
//                    border: 'none',
//                    padding: '15px',
//                    backgroundColor: 'transparent',
//                    '-webkit-border-radius': '10px',
//                    '-moz-border-radius': '10px',
//                    'border-radius': '10px',
//                    color: '#fff'
//                },
//                message: '<div class="alert alert-danger" role="alert" style="font-size: 20px">'+errorAjax+'</div>', baseZ: 99999                     
//            });
//            setTimeout($.unblockUI, 3000);
//        }
//    });
//}
//
///**
// * Codigo Pedro Hernández Villegas
// * @type {string}
// */
//var hour = ''; // Hora de la cita
//var idRoom = ''; // Id de la sala
//
//// Se captura la fecah y hora de la cita
//$(document).on('click', '.btn.btn-success.form-control', function () {
//    hour = $(this).val();
//});
//// Se captura el ide de la sala
//$(document).on('click', '.salas', function () { idRoom = $(this).val(); });
//
//
//function getDisponibility(sala,date,user, modificar){
//    
////    var salaActive = document.getElementsByClassName("active");
//       $.ajax({
//                        url: UrlDisponibility,
//                        type: 'POST',
//                        data: {'room': sala ,'date': date , 'user':user },
//                        success: function( data ){
//                            var obj = JSON.parse(data);
//                            console.log(obj);
//                            $.unblockUI();
//
//                            $('#idScheRu').attr('value',obj.idScheRu);
//                            $('#tituloDisp').html('');
//                            $('#tituloDisp').html(obj.TituloDisp);
//
//                            $('#DispMes').html('');
//                            $('#DispMes').html(obj.tbodymes);
//                            if(user ==''){
//                                $('#div-dispRoom').html('');
//                                $('#div-dispRoom').html(obj.data);
//                            }else{
//                                $('#div-dispUser').html('');
//                                $('#div-dispUser').html(obj.data);
//                                //alert('Entra a usuarios');
//
//                            }
//                            if(obj.sw == 0){
//                                $('#div-multi').show('fast');
//                            }else{
//                                $('#div-multi').hide('fast');
//                            }
//                            $('[data-toggle="tooltip"]').tooltip();
//                            //$('.basic').append(obj.studyList);
//                             getStudies(obj.studyList,'');
//                             $('#idModality').prop('value',obj.studyList);
//                             $('#idModalityUpdate').prop('value',obj.studyList);
//                             //$('#provenance').html(obj.proList).addClass('selectpickerAll');
//                             /*$('#divMedico').html(obj.medList);
//                             $('#medico').addClass('selectpickerAll');*/
//                             //$('#divMedicoCH').html(obj.medListCH);
//                             //$('#medicoCH').addClass('selectpickerAll');
//                             $('#divprovenance').html(obj.proList);
//                             $('#provenance').addClass('selectpickerAll');
//                             $('#diveps').html(obj.Eps);
//                             $('#eps').addClass('selectpickerAll');
//                             $('#divsms').html(obj.sms);
//                             $('#sms').addClass('selectpickerAll');
//                             $('#divprovenanceCH').html(obj.proListCH);
//                             $('#provenanceCH').addClass('selectpickerAllCH');
//                             $('#divepsCH').html(obj.EpsCH);
//                             $('#epsCH').addClass('selectpickerAllCH');
//                             $('#divsmsCH').html(obj.smsCH);
//                             $('#smsCH').addClass('selectpickerAllCH');
//                             $('#reserva').addClass('selectpickerAll');
//                             $('#reservaCH').addClass('selectpickerAllCH');
//                             //$('div .medicos').html('');
//                             $('.selectpickerAll').selectpicker();
//                             $('.selectpickerAllCH').selectpicker();
////                             $('.selectpickerAll').change(function(){
////                                 var valor = $(this).prop('value');
////                                 var idsel = $(this).prop('id');
////                                 //alert(idsel);
////                                 $('#txt'+idsel).val(valor);
////                                 $(this).removeClass('btn-danger').addClass('btn-primary');
////                             });
//                             $('div .medicos').html(obj.user);
//                             $('.users').click(function(){
//                                var idUser = $(this).prop('value');
////                                var idScheRu = $('#idScheRu').val();
//                                var dateU = $('#date-search').val();
//                                var idScheRu = $(this).attr('data-idscheru');
//                                day = $(this).attr('data-days');
//                                //alert(idUser);
//                                $('#idUser').prop('value',idUser);
//                                $('#idUserUpdate').prop('value',idUser);
//                                $.blockUI({ css: {
//                                    border: 'none',
//                                    padding: '15px',
//                                    '-webkit-border-radius': '10px',
//                                    '-moz-border-radius': '10px',
//                                    'border-radius': '10px',
//                                    color: '#fff'
//
//                                },message:'<h3><img style="width: 40px;" src="'+SrcGif+'" style="border: none;"/>'+cargando+'...' , baseZ: 99999 });
//                                getDisponibilityUser(sala,dateU,idUser,idScheRu,day);
//                            });
//                            $('.btn-success').click(function(){
//                                var valbtn = $(this).prop('value');
//                                var idBtn = $(this).prop('id');
//                                var duration = $(this).attr('data-id');
//                                var idScheRu = $(this).attr('data-idscheru');
//                                typeAction = 'normal';
//                                btnSuccessAction(valbtn,idBtn,sala,duration,idScheRu,'');
//                             });
//
//                             $('.btn-special').click(function(){
//                                var valbtn = $(this).prop('value');
//                                var idBtn = $(this).prop('id');
//                                var duration = $(this).attr('data-id');
//                                var idScheRu = $(this).attr('data-idscheru');
//                                var idSch = $(this).attr('data-idsch');
//                                $('#idschSpecial').attr('value',idSch);
//                                typeAction = 'special';
//
//                               // alert('pasa parametros antes de bt valbtn:'+valbtn+' idBtn:'+idBtn+' duration:'+duration+' idScheRu:'+idScheRu+'');
//                                btnSuccessAction(valbtn,idBtn,sala,duration,idScheRu,idSch);
//                             });
//
//                            $('.btn-danger').click(function(){
//                              var idSche = $(this).prop('value');
//                              $('#idSche').attr('value' , idSche);
//                               btnDangerAction(idSche,'','');
//                            });
//
//                            $('.btn-info').click(function(){
//                                var idSche = $(this).prop('value');
//                                var user = $('#idUser').prop('value');
//                                //alert('idSche:'+idSche+' user:'+user);
//                                $('#idSche').attr('value' , idSche);
//                                btnDangerAction(idSche,user,1);
//                            });
//                            $('.btn-success2').click(function(){
//                                var date = $(this).attr('id');
//                                var salach = '';
//                                if(update_Insert == true){
//                                    salach = $('#room-searchUpdate').val();
//                                }else if(update_Insert == false){
//                                    salach = $('#room-search').val();
//                                }
//                                //var dateSearch = $('#date-search').val();
//                                //var user = $('#idUser').val();
//                                var idScheRu = $('#idScheRu').val();
//                                $('#date-search').prop('value',date);
//                                $('#date-searchUpdate').prop('value',date);
//                                if(salach !=''){
//                                    var dateSearch = '';
//                                    var user = '';
//                                    if(update_Insert == true) {
//                                        dateSearch = $('#date-searchUpdate').val();
//                                        user = $('#idUserUpdate').val();
//                                    }else if(update_Insert == false){
//                                        dateSearch = $('#date-search').val();
//                                        user = $('#idUser').val();
//                                    }
//                                    if(user!=''){
//                                        getDisponibilityUser(salach,dateSearch,user,idScheRu,day);
//                                    }else{
//                                        getDisponibility(salach,dateSearch,user);
//                                    }
//                                }
//                            });
//
//                            $('.success').click(function(){
//                                var salach = '';
//                                var date = $(this).attr('data-dateSearch');
//                                if(update_Insert == true){
//                                    salach = $('#room-searchUpdate').val();
//                                }else if(update_Insert == false){
//                                    salach = $('#room-search').val();
//                                }
//                                //var dateSearch = $('#date-search').val();
//                                //var user = $('#idUser').val();
////                                var idScheRu = $(this).data('idscheru');
//                                $('#date-search').prop('value',date);
//                                $('#date-searchUpdate').prop('value',date);
//                                if(salach !=''){
//                                    var dateSearch = '';
//                                    var user = '';
//                                    if(update_Insert == true) {
//                                        dateSearch = $('#date-searchUpdate').val();
//                                        user = $('#idUserUpdate').val();
//                                    }else if(update_Insert == false){
//                                        dateSearch = $('#date-search').val();
//                                        user = $('#idUser').val();
//                                    }
////                                    if(user!=''){
////                                        getDisponibilityUser(salach,dateSearch,user,idScheRu,day);
////                                    }else{
//                                    $.blockUI({ css: {
//                                        border: 'none',
//                                        padding: '15px',
//                                        '-webkit-border-radius': '10px',
//                                        '-moz-border-radius': '10px',
//                                        'border-radius': '10px',
//                                        color: '#fff'
//
//                                    },message:'<h3><img style="width: 40px;" src="'+SrcGif+'" style="border: none;"/>'+cargando+'...' , baseZ: 99999 });
//                                        getDisponibility(salach,dateSearch,'');
////                                    }
//                                }
//                            });
//
//
//                        }
//                    })
//                    return false;
//
//
//}
//
//function dataModalUpdate(idSche,type){
//    $.ajax({
//        url: UrlConsultAppointment,
//        type: 'POST',
//        data: {'idSche': idSche},
//        success: function (data) {
//            var obj = JSON.parse(data);
//            console.log(obj);
//            if(type == 0){
//                $('#info-patientUpdate').html('<label class="col-sm-12" id="patientNowUpdate">' + obj[0][0]['document'] + ' - ' + obj[0][0]['firstName'] + ' ' + obj[0][0]['secondName'] + ' ' + obj[0][0]['firstLastName'] + ' ' + obj[0][0]['secondLastName'] + '</label>');
//                $('#info-studyUpdate').html('');
//                for (var i = 0; i < obj[0].length; i++) {
//                    $('#info-studyUpdate').append('<label class="col-sm-12" >' + (i + 1) + '. ' + obj[0][i]['studyName'] + '</label');
//                }
//                $('#info-provenanceUpdate').html('<label class="col-sm-12" >' + obj[0][0]['PROName'] + '</label>');
//                $('#info-convenioUpdate').html('<label class="col-sm-12" >' + obj[0][0]['EPSName'] + '</label>');
//                if (obj[0][0]['reservation'] == 0) {
//                    var reserv = 'Presencial';
//                } else {
//                    var reserv = 'Telefonica';
//                }
//                $('#info-reservationUpdate').html('<label class="col-sm-12" >' + reserv + '</label>');
//                $('#info-smsUpdate').html('<label class="col-sm-12" >' + obj[0][0]['SMSName'] + '</label>');
//                $('#info-commentUpdate').html('<p class="col-sm-12" >' + obj[0][0]['comment'] + '</p>');
//            }else{
//                $('#pacienteCH').html('<label class="col-sm-12" >' + obj[0][0]['document'] + ' - ' + obj[0][0]['firstName'] + ' ' + obj[0][0]['secondName'] + ' ' + obj[0][0]['firstLastName'] + ' ' + obj[0][0]['secondLastName'] + '</label>');
//                //$("#provenanceCH option[value='"+obj[0][0]['proId']+"']").prop('selected',true);
//                $("#provenanceCH").selectpicker('val',obj[0][0]['proId']);
//                $("#epsCH").selectpicker('val',obj[0][0]['idEps']);
//                $("#smsCH").selectpicker('val',obj[0][0]['SMSid']);
//                $("#reservaCH").selectpicker('val',obj[0][0]['reservation']);
//                $('#commentsCH').val(obj[0][0]['comment']);
//                var mod = $('#idModality').val();
//                getStudies(mod,count);
//                $('#modalChange').modal('show');
//            }
//        }
//    })
//}
//
//
//function getReservation(sala,valbtn,idScheRu,ch,idBtn,duration,idScheMulti,update_Insert){
//    $.ajax({
//        url: UrlScheduleReservation,
//        type: 'POST',
//        data: {'room': sala ,'date': valbtn ,'idScheRu': idScheRu,'duration': duration },
//        success: function(dataReserv){
//        var obj = JSON.parse(dataReserv);
//        console.log(obj);
//        if(obj){
//            $.ajax({
//                url: UrlFindScheduleUser,
//                type: 'POST',
//                data: {'date': valbtn ,'idScheRu': idScheRu },
//                success: function(dataReserv1) {
//
//                    var obj1 = JSON.parse(dataReserv1);
//                    console.log(obj1);
//                    $('#divMedico').html('');
//                    $('#divMedico').html(obj1.medList);
//                    $('#medico').addClass('selectpickerAll');
//                    $('.selectpickerAll').selectpicker();
//
//
//                    if(ch){
//                        $('#'+idBtn).removeClass("btn-success");
//                        if(idScheMulti == ''){
//                            idScheMulti = idScheMulti+obj.idSchedule;
//                            $('#idInit').prop('value',idBtn);
//                            $('#idEnd').prop('value',idBtn);
//                            $('#idInitUpdate').prop('value',idBtn);
//                            $('#idEndUpdate').prop('value',idBtn);
//                        }else{
//                            idScheMulti = idScheMulti +','+obj.idSchedule;
//                            $('#idEnd').prop('value',idBtn);
//                            $('#idEndUpdate').prop('value',idBtn);
//                        }
//                        $('#idSchedule').prop('value',idScheMulti);
//                        $('#idScheduleUpdate').prop('value',idScheMulti);
//
//                        $('#'+idBtn).addClass("btn-danger");
//                    }else{
//                        $('#idSchedule').prop('value',obj.idSchedule);
//                        $('#idScheduleUpdate').prop('value',obj.idSchedule);
//                        var idSche = $('#CodScheduleSelect').val();
//
//                        var idScheRu = $(this).attr('data-idscheru');
//                        var salach = '';
//                        var date = $(this).attr('data-dateSearch');
//                        if(update_Insert == true){
//                            salach = $('#room-searchUpdate').val();
//                        }else if(update_Insert == false){
//                            salach = $('#room-search').val();
//                        }
//                        //var dateSearch = $('#date-search').val();
//                        //var user = $('#idUser').val();
////                                var idScheRu = $(this).data('idscheru');
//                        $('#date-search').prop('value',date);
//                        $('#date-searchUpdate').prop('value',date);
//                        if(update_Insert == true){
//                            var dateSearch = '';
//                            var user = '';
//                            if(update_Insert == true) {
//                                dateSearch = $('#date-searchUpdate').val();
//                                user = $('#idUserUpdate').val();
//                            }else if(update_Insert == false){
//                                dateSearch = $('#date-search').val();
//                                user = $('#idUser').val();
//                            }
//                            if(user!=''){
//                                getDisponibilityUser(salach,dateSearch,user,idScheRu,day);
//                            }else{
//                                getDisponibility(salach,dateSearch,user);
//                            }
//                            dataModalUpdate(idSche,0);
//                            $('#modalUpdate').modal('show');
//                            //}
//
//                        }else if(update_Insert == false){
//                            $('#modal').modal('show');
//                            $(window).on('shown.bs.modal', function(){
//                                $('#tags').focus();
//                            });
//                        }
//                    }
//                    $.unblockUI();
//                }
//            })
//        }else{
//
//            $.blockUI({ css: {
//                    border: 'none',
//                    padding: '15px',
//                    '-webkit-border-radius': '10px',
//                    '-moz-border-radius': '10px',
//                    'border-radius': '10px',
//                    color: '#fff'
//
//                },message:'<div class="alert alert-danger" role="alert" style="font-size:30px; margin:0; padding:0;" > <span class="glyphicon glyphicon-alert" aria-hidden="true"></span> <span class="sr-only">Error:</span>'+espacioUso+'</div>' });
//            setTimeout($.unblockUI, 2500);
//        }
//        //alert(obj.idSchedule);
//        //$.unblockUI();
//        }
//    })
//    return false;
//
//}
//
//
//function getDisponibilityUser(sala,date,user,idScheRu,day){
//       $.ajax({
//                        url: UrlDisponibilityUser,
//                        type: 'POST',
//                        data: {'room': sala ,'date': date , 'user':user ,'idScheRu':idScheRu , 'day':day },
//                        success: function( data ){
//                            var obj = JSON.parse(data);
//                            console.log(obj);
//                            $.unblockUI();
//                            $('#tituloDisp').html('');
//                            $('#tituloDisp').html(obj.TituloDisp);
//
//                            $('#div-dispUser').html('');
//                            $('#div-dispUser').html(obj.data);
//
//                            /*$('#divMedico').html(obj.medList);
//                            $('#medico').addClass('selectpickerAll');
//                            $('.selectpickerAll').selectpicker();*/
//
//                            $('#div-multi').show('fast');
//                            $('.btn-success').click(function(){
//                                var valbtn = $(this).prop('value');
//                                var idBtn = $(this).prop('id');
//                                var duration = $(this).attr('data-id');
//                                var idScheRu = $(this).attr('data-idscheru');
//                                typeAction = 'normal';
//                                btnSuccessAction(valbtn,idBtn,sala,duration,idScheRu,'');
//                            });
//
//                            $('.btn-special').click(function(){
//                                var valbtn = $(this).prop('value');
//                                var idBtn = $(this).prop('id');
//                                var duration = $(this).attr('data-id');
//                                var idScheRu = $(this).attr('data-idscheru');
//                                typeAction = 'special';
//                                var idSch = $(this).attr('data-idsch');
//                                $('#idschSpecial').attr('value',idSch);
//                                btnSuccessAction(valbtn,idBtn,sala,duration,idScheRu,idSch);
//                             });
//
//                            $('.btn-danger').click(function(){
//                                var idSche = $(this).prop('value');
//                                $('#idSche').attr('value' , idSche);
//                                btnDangerAction(idSche,user,'');
//
//                            });
//
//                            $('.btn-info').click(function(){
//                                var idSche = $(this).prop('value');
//                                var user = $('#idUser').prop('value');
//                                $('#idSche').attr('value' , idSche);
//                                //alert('idSche:'+idSche+' user:'+user);
//                                btnDangerAction(idSche,user,1);
//                            });
//
//                            $( "#calendar" ).datepicker({
//                                onSelect: function( date ){
//                                    var salach = '';
//                                    if(update_Insert == true){
//                                        salach = $('#room-searchUpdate').val();
//                                    }else if(update_Insert == false){
//                                        salach = $('#room-search').val();
//                                    }
//                                    //var dateSearch = $('#date-search').val();
//                                    //var user = $('#idUser').val();
//                                    var idScheRu = $('#idScheRu').val();
//                                    $('#date-search').prop('value',date);
//                                    $('#date-searchUpdate').prop('value',date);
//                                    if(salach !=''){
//                                        var dateSearch = '';
//                                        var user = '';
//                                        if(update_Insert == true) {
//                                            dateSearch = $('#date-searchUpdate').val();
//                                            user = $('#idUserUpdate').val();
//                                        }else if(update_Insert == false){
//                                            dateSearch = $('#date-search').val();
//                                            user = $('#idUser').val();
//                                        }
//                                        if(user!=''){
//                                            getDisponibilityUser(salach,dateSearch,user,idScheRu,day);
//                                        }else{
//                                            getDisponibility(salach,dateSearch,user);
//                                        }
//                                    }
//
//                            },
//                            dateFormat: 'yy-mm-dd',
//                              numberOfMonths: 2,
//                        });
//
//                                                    //para usuario
//                            $('.success2').click(function(){
//                                var salach = '';
//                                var date = $(this).attr('data-dateSearch');
//                                if(update_Insert == true){
//                                    salach = $('#room-searchUpdate').val();
//                                }else if(update_Insert == false){
//                                    salach = $('#room-search').val();
//                                }
//                                //var dateSearch = $('#date-search').val();
//                                //var user = $('#idUser').val();
//                                var idScheRu = $(this).data('idscheru');
//                                $('#date-search').prop('value',date);
//                                $('#date-searchUpdate').prop('value',date);
//                                if(salach !=''){
//                                    var dateSearch = '';
//                                    var user = '';
//                                    if(update_Insert == true) {
//                                        dateSearch = $('#date-searchUpdate').val();
//                                        user = $('#idUserUpdate').val();
//                                    }else if(update_Insert == false){
//                                        dateSearch = $('#date-search').val();
//                                        user = $('#idUser').val();
//                                    }
////                                    if(user!=''){
//                                    $.blockUI({ css: {
//                                        border: 'none',
//                                        padding: '15px',
//                                        '-webkit-border-radius': '10px',
//                                        '-moz-border-radius': '10px',
//                                        'border-radius': '10px',
//                                        color: '#fff'
//
//                                    },message:'<h3><img style="width: 40px;" src="'+SrcGif+'" style="border: none;"/>'+cargando+'...' , baseZ: 99999 });
//                                        getDisponibilityUser(salach,dateSearch,user,idScheRu,day);
////                                    }else{
////                                        getDisponibility(salach,dateSearch,user);
////                                    }
//                                }
//                            });
//
//                        }
//                    })
//                    return false;
//
//
//}
//
//function getCancelReservation(idSche,sala,valbtn,user){
//    $.blockUI({ css: {
//        border: 'none',
//        padding: '15px',
//        '-webkit-border-radius': '10px',
//        '-moz-border-radius': '10px',
//        'border-radius': '10px',
//        color: '#fff'
//
//    },message:'<h3><img style="width: 40px;" src="'+SrcGif+'" style="border: none;"/>'+wait+'...' , baseZ: 99999 });
//    $.ajax({
//        url: UrlScheduleOption,
//        type: 'POST',
//        data: {'idSche': idSche },
//        success: function(data){
//            var obj = JSON.parse(data);
//            console.log(obj);
//            $('#PrintIndications').attr('href', '#');
//            $('#PrintIndications').hide();
//            $('.modal').modal('hide');
//            if(user != ''){
//
//                var idScheRu = $('#idScheRu').val();
//                getDisponibilityUser(sala,valbtn,user,idScheRu,day);
////                if(update_Insert == true){
////                    $('#modalUpdate').modal('hide');
////                }else if(update_Insert == false){
////                    $('#modal').modal('hide');
////                    $('#modalUpdate').modal('hide');
////                }
//            }else{
//                getDisponibility(sala,valbtn,user);
//                if(update_Insert == true){
//                    if(changeStudy == true){
////                        $('#modalChange').modal('hide');
//                        changeStudy = false;
//                    }
////                    else{
////                        $('#modalUpdate').modal('hide');
////                    }
//                }
////                else if(update_Insert == false){
////                    $('#modal').modal('hide');
////                }
//            }
//            if(update_Insert == true){
//                $('#idScheduleUpdate').prop('value','');
//                $('#idInitUpdate').prop('value','');
//                $('#idEndUpdate').prop('value','');
//            }else if(update_Insert == false){
//                $('#idSchedule').prop('value','');
//                $('#idInit').prop('value','');
//                $('#idEnd').prop('value','');
//            }
//            //$.unblockUI();
//        }
//    })
//    return false;
//}
//
//function btnSuccessAction(valbtn,idBtn,sala,duration,idScheRu){
//    $.blockUI({ css: {
//        border: 'none',
//        padding: '15px',
//        '-webkit-border-radius': '10px',
//        '-moz-border-radius': '10px',
//        'border-radius': '10px',
//        color: '#fff'
//
//    },message:'<h3><img style="width: 40px;" src="'+SrcGif+'" style="border: none;"/> '+wait+'...' , baseZ: 99999 });
//                                  //alert(new Date().toUTCString());
//                                var salaName = $('#tituloDisp').html();
//                                var NomSala = salaName.split(":");
//                                var fechaf = valbtn.split(" ");
//                                var fechacoma = fechaf[0].split("-");
//                                var fecha = new Date(fechacoma[0], fechacoma[1]-1, fechacoma[2]);
//
//                                var dia = fecha.getDate();
//                                var diatxt = fecha.getDay()-1;
//                                var mes = fecha.getMonth();
//                                var anno = fecha.getFullYear();
//
////                                var diasAr = ["Lunes" , "Martes" , "Miercoles" , "Jueves" , "Viernes" , "Sabado" , "Domingo"];
////                                var mesAr = ["Enero","Febrero","Marzo","Abril","Mayo","Junio","Julio","Agosto","Septiembre","Octubre","Noviembre","Diciembre"];
//                                //alert(anno+'/'+mesAr[mes]+'/'+dia+'/'+diasAr[diatxt]);
//                                //var dateSelected = fecha.getDay();
//                                //
//                                //var dateSelected = fecha.getDate();
//                                //alert(fecha);
//
//                                var ch = $('#selectMulti').prop('checked');
//                                var dateSelected = dateFormat(fecha,"fullDate");
//                                //var idScheRu = $('#idScheRu').val();
//                                var idScheMulti = $('#idSchedule').val();
//                                var idInit = '';
//                                var idEnd = '';
//                                var nomSalaCH = $('#nomSalaChange').val();
//                                if (update_Insert == true){
//                                    $('#timeDateUpdate').prop('value',valbtn);
//                                    if( nomSalaCH != NomSala[1]){
//                                        var appCodeCH = $('#idSchedule').val();
//                                        changeStudy = true;
//                                    }else{
//                                       $('#fechaasigUpdate').html(diasAr[diatxt]+', '+mesAr[mes]+' '+dia+', '+anno+' - hora: '+fechaf[1]+' '+room+':'+NomSala[1]);
//                                        $('#NomSalaUpdate').html('').html(' '+room+':'+NomSala[1]);
//                                        idScheMulti =$('#idScheduleUpdate').val();
//                                        $('#timeDateUpdate').prop('value',valbtn);
//                                        idInit = $('#idInitUpdate').prop('value');
//                                        idEnd = $('#idEndUpdate').prop('value');
//                                    }
//
//                                }else if(update_Insert == false){
//                                    $('#fechaasig').html(diasAr[diatxt]+', '+mesAr[mes]+' '+dia+', '+anno+' - hora: '+fechaf[1]);
//                                    $('#NomSala').html('').html(' '+room+':'+NomSala[1]);
//                                    idScheMulti =$('#idSchedule').val();
//                                    $('#timeDate').prop('value',valbtn);
//                                    idInit = $('#idInit').prop('value');
//                                    idEnd = $('#idEnd').prop('value');
//                                }
//
////                                var duration = $('#duration').prop('value');
//                                var dateMin=new Date();
//                                var actYear = dateMin.getFullYear();
//                                if((dateMin.getMonth()) < 9){
//                                    var actMonth = '0'+(dateMin.getMonth()+1);
//                                }else{
//                                     var actMonth = (dateMin.getMonth()+1);
//                                }
//
//                                if((dateMin.getDate()) < 10){
//                                    var actDay = '0'+dateMin.getDate();
//                                }else{
//                                     var actDay = dateMin.getDate();
//                                }
//                                var actHour = dateMin.getHours()+''+dateMin.getMinutes();
//                                var actFullDate = actYear+'-'+actMonth+'-'+actDay;
//                                //var idhora = parseInt($(this).prop('id'));
//                                var selectD = valbtn.split(" ");
//                                var selectDate = Date.parse(selectD[0]);
//                                var horas = selectD[1].split(":");
//                                var selH = horas[0]+''+horas[1];
//                                var selectHour = parseInt(selH);
//                                var actualH = parseInt(actHour);
//                                var actualDate = Date.parse(actFullDate);
//                                //alert('date:'+dateMin+'mes:'+actMonth+'dia:'+actDay+' act:'+actFullDate+' parce:'+actualDate+' select:'+selectD[0]+' parce:'+selectDate);
//
//
//                                if((actualDate > selectDate)){
//                                        $.blockUI({ css: {
//                                            border: 'none',
//                                            padding: '15px',
//                                            '-webkit-border-radius': '10px',
//                                            '-moz-border-radius': '10px',
//                                            'border-radius': '10px',
//                                            color: '#fff'
//
//                                        },message:'<div class="alert alert-danger" role="alert" style="font-size:30px; margin:0; padding:10px;" > <span class="glyphicon glyphicon-alert" aria-hidden="true"></span> <span class="sr-only">Error:</span>'+fecPasada+'</div>' });
//                                        setTimeout($.unblockUI, 3000);
//                                }else{
//                                    if(actualDate == selectDate){
//                                        //alert('actual'+actualH+'    select'+selectHour);
//                                        if(actualH > selectHour){
//                                            $.blockUI({ css: {
//                                                border: 'none',
//                                                padding: '15px',
//                                                '-webkit-border-radius': '10px',
//                                                '-moz-border-radius': '10px',
//                                                'border-radius': '10px',
//                                                color: '#fff'
//
//                                            },message:'<div class="alert alert-danger" role="alert" style="font-size:30px; margin:0; padding:10px;" > <span class="glyphicon glyphicon-alert" aria-hidden="true"></span> <span class="sr-only">Error:</span>'+horaPasada+'</div>' });
//                                            setTimeout($.unblockUI, 3000);
//                                        }else{
//                                            if(ch){
//                                                if(idEnd == ''){
//                                                    getReservation(sala,valbtn,idScheRu,ch,idBtn,duration,idScheMulti,update_Insert,typeAction);
//                                                }else{
//                                                    var aux = parseInt(idEnd) + parseInt(duration);
//                                                    var aux2 = aux.toString();
//                                                    var auxstr;
//                                                    if(aux2 < 1000){
//                                                        auxstr = aux2.substring(1);
//                                                    }else{
//                                                        auxstr = aux2.substring(2);
//                                                    }
//                                                    if( auxstr == 60){
//                                                        aux = aux+40;
//                                                    }
//
//                                                    if(aux == idBtn){
//                                                        getReservation(sala,valbtn,idScheRu,ch,idBtn,duration,idScheMulti,update_Insert,typeAction);
//                                                    }else{
//                                                        $.blockUI({ css: {
//                                                            border: 'none',
//                                                            padding: '15px',
//                                                            '-webkit-border-radius': '10px',
//                                                            '-moz-border-radius': '10px',
//                                                            'border-radius': '10px',
//                                                            color: '#fff'
//
//                                                        },message:'<div class="alert alert-danger" role="alert" style="font-size:30px; margin:0; padding:10px;" > <span class="glyphicon glyphicon-alert" aria-hidden="true"></span> <span class="sr-only">Error:</span>'+espConsecutivos+'</div>' });
//                                                        setTimeout($.unblockUI, 2000);
//                                                    }
//                                                }
//                                            }else{
//                                                getReservation(sala,valbtn,idScheRu,'','',duration,'',update_Insert,typeAction);
//                                            }
//                                        }
//                                    }else{
//                                        if(ch){
//                                                if(idEnd == ''){
//                                                    getReservation(sala,valbtn,idScheRu,ch,idBtn,duration,idScheMulti,update_Insert,typeAction);
//                                                }else{
//                                                    var aux = parseInt(idEnd) + parseInt(duration);
//                                                    var aux2 = aux.toString();
//                                                    var auxstr;
//                                                    if(aux2 < 1000){
//                                                        auxstr = aux2.substring(1);
//                                                    }else{
//                                                        auxstr = aux2.substring(2);
//                                                    }
//                                                    if( auxstr == 60){
//                                                        aux = aux+40;
//                                                    }
//
//                                                    if(aux == idBtn){
//                                                        getReservation(sala,valbtn,idScheRu,ch,idBtn,duration,idScheMulti,update_Insert,typeAction);
//                                                    }else{
//                                                        $.blockUI({ css: {
//                    border: 'none',
//                    padding: '15px',
//                    '-webkit-border-radius': '10px',
//                    '-moz-border-radius': '10px',
//                    'border-radius': '10px',
//                    color: '#fff'
//
//                },message:'<div class="alert alert-danger" role="alert" style="font-size:30px; margin:0; padding:10px;" > <span class="glyphicon glyphicon-alert" aria-hidden="true"></span> <span class="sr-only">Error:</span>'+espConsecutivos+'</div>' });
//                                                        setTimeout($.unblockUI, 2000);
//                                                    }
//                                                }
//                                            }else{
//                                                getReservation(sala,valbtn,idScheRu,'','',duration,'',update_Insert,typeAction);
//                                            }
//                                    }
//                                }
//
//
//}
//
//
//function btnDangerAction(idSche,user,cfm){
//
//    $.blockUI({ css: {
//                    border: 'none',
//                    padding: '15px',
//                    '-webkit-border-radius': '10px',
//                    '-moz-border-radius': '10px',
//                    'border-radius': '10px',
//                    color: '#fff'
//
//                },message:'<h3><img style="width: 40px;" src="'+SrcGif+'" style="border: none;"/> '+wait+'...' , baseZ: 99999 });
//$.ajax({
//    url: UrlConsultAppointment,
//    type: 'POST',
//    data: {'idSche': idSche},
//    cache: false,
//    success: function ( data ) {
//        var obj = JSON.parse(data);
//        console.log(obj); // VARIABLE QUE TOMA LO QUE RETOMA EL AJAX
//        if(obj == 0){
//            $.blockUI({ css: {
//                    border: 'none',
//                    padding: '15px',
//                    '-webkit-border-radius': '10px',
//                    '-moz-border-radius': '10px',
//                    'border-radius': '10px',
//                    color: '#fff'
//
//                },message:'<div class="alert alert-danger" role="alert" style="font-size:30px; margin:0; padding:10px;" > <span class="glyphicon glyphicon-alert" aria-hidden="true"></span> <span class="sr-only">Error:</span>'+errorCita+'</div>' });
//                                                        setTimeout($.unblockUI, 2000);
//        }else{
//            $('#consultAgenda').modal('hide');
//            /*----------------Blanquear campos para ingresar informacion de la cita*/
//                $('#info-CodScheduleSms').html('');
//                $('#info-codeScheduleSelect').html('');
//                $('#info-codeApp').html('');
//                $('#info-dateApp').html('');
//
//                $('#info-patient').html('');
//                $('#info-pat').html('');
//                $('#info-telephone').html('');
//                $('#info-cellphone').html('');
//                $('#info-email').html('');
//                $('#info-provenance').html('');
//                $('#info-convenio').html('');
//                $('#info-reservation').html('');
//                $('#info-sms').html('');
//                $('#info-commentAssign').html('');
//                $('#info-commentConfirm').html('');
//                $('#info-medicoAsignado').html('');
//
//                if(obj[0][0]['appointmentStatus'] >= 3){
//                    $('#appModify').hide('fast');
//                    $('#appCancel').hide('fast');
//                    $('#labelR').removeClass('hide');
//                }else{
//                    $('#appModify').show('fast');
//                    $('#appCancel').show('fast');
//                    $('#labelR').addClass('hide');
//                }
//
//            /*------- fin blanquear campos--------*/
//
//            $('#info-CodScheduleSms').html('<input type="text" id="CodScheduleSms1" name="CodScheduleSms1" value=' + obj[0][0]["SMSid"] + ' />');
//            $('#info-codeScheduleSelect').html('<input type="text" id="codeScheduleSelect1" name="codeScheduleSelect1" value=' + obj[0][0]["idSchedule"] + ' />');
//            $('#info-codeApp').html('<input type="text" id="appCode1" name="appCode1" value=' + obj[0][0]["appointmentCode"] + ' />');
//            $('#info-dateApp').html('');
//
//            $('#info-patient').html('<label class="col-sm-12" id="patientNow">' + obj[0][0]['document'] + ' - ' + obj[0][0]['firstName'] + ' ' + obj[0][0]['secondName'] + ' ' + obj[0][0]['firstLastName'] + ' ' + obj[0][0]['secondLastName'] + '</label>');
//            $('#info-pat').html('<h5>' + obj[0][0]['document'] + ' - ' + obj[0][0]['firstName'] + ' ' + obj[0][0]['secondName'] + ' ' + obj[0][0]['firstLastName'] + ' ' + obj[0][0]['secondLastName'] + '</h5>');
//            $('#info-telephone').html('<label class="col-sm-12" >' + obj[0][0]['telephone'] + '</label');
//            $('#info-cellphone').html('<label class="col-sm-12" >' + obj[0][0]['cellphone'] + '</label');
//            $('#info-email').html('<label class="col-sm-12" >' + obj[0][0]['email'] + '</label');
//            $('#info-study').html('');
//            for (var i = 0; i < obj[0].length; i++) {
//                $('#info-study').append('<label class="col-sm-12" >' + (i + 1) + '. ' + obj[0][i]['studyName'] + '</label');
//            }
//            $('#info-provenance').html('<label class="col-sm-12" >' + obj[0][0]['PROName'] + '</label>');
//            $('#info-convenio').html('<label class="col-sm-12" >' + obj[0][0]['EPSName'] + '</label>');
//            if (obj[0][0]['reservation'] == 0) {
//                var reserv = 'Presencial';
//            } else {
//                var reserv = 'Telefonica';
//            }
//            $('#info-reservation').html('<label class="col-sm-12" >' + reserv + '</label>');
//            $('#info-sms').html('<label class="col-sm-12" >' + obj[0][0]['SMSName'] + '</label>');
//            for (var j = 0; j < obj['logsAsig'].length; j++) {
//                $('#info-commentAssign').append('<li class="col-sm-12" >' + obj['logsAsig'][j]['description'] + '</li>');
//            }
////            $('#info-commentAssign').html('<p class="col-sm-12" >' + obj[0][0]['comment'] + '</p>');
//            for (var k = 0; k < obj['logsConf'].length; k++) {
//                $('#info-commentConfirm').append('<li class="col-sm-12" >' + obj['logsConf'][k]['description'] + '</li>');
//            }
//            /*if(obj['logsConf'] != ''){
//               $('#info-commentConfirm').html('<label class="col-sm-3"><b>Comentario Confirmación: </b></label><div class="col-sm-9" ><p class="col-sm-12" >' + obj['logsConf'][0]['description'] + '</p></div>');
//            }*/
//            $('#info-medicoAsignado').html('<p class="col-sm-12" >' + obj[0][0]['firstname'] + ' ' + obj[0][0]['lastname'] +'</p>');
//
//           // $('#info-horaInicial').html('<p class="col-sm-12" >' + obj[0]['hoursStart']);
//
//           // $('#info-horaFinal').html('<p class="col-sm-12" >' + obj[0]['hoursEnd']);
//
//            $('#info-horario').html('');
//            $('#info-horaInicial').html('');        // para borrar las hora incial, final y fecha
//            $('#info-horaFinal').html('');
//
//            var contCitas = 0;
//            for (var i = 0; i < obj[0].length; i++) {
//                $('#info-horario').append('<label class="col-sm-12" >' + (i + 1) + '. ' + obj[0][i]['dateStart'] + '</label');
//                $('#info-horaInicial').append('<label class="col-sm-12" >' + (i + 1) + '. ' + obj[0][i]['hoursStart'] + '</label');
//                $('#info-horaFinal').append('<label class="col-sm-12" >' + (i + 1) + '. ' + obj[0][i]['hoursEnd'] + '</label');
//                $('#info-dateApp').append('<h5>' + obj[0][i]['dateStart'] + ' / ' + obj[0][i]['hoursStart'] + ' - '+obj[0][i]['hoursEnd']+'</h5>');
//                contCitas = contCitas+1;
//            }
//            $('#ContCitas').attr('value',contCitas);
//            $('#appComments').attr('value',idSche);
//           $('#modalinfo').modal('show');
//           $.unblockUI();
//            if (cfm) {
//                $('.btn-action').hide('fast');
//            } else {
//            $('.btn-action').show('fast');
//                   }
//
////Empieza el motivo para cancelar la cita
//             $('#appCancel').on("click",function (event) {
//             	$('#modalinfo').modal('hide');
//                $.blockUI({
//                    css: {
//                       border: 'none',
//                       padding: '15px',
//                       '-webkit-border-radius': '10px',
//                       '-moz-border-radius': '10px',
//                       'border-radius': '10px',
//                       cursor:'auto',
//                       color: 'red'
//                    },
//                    message:$('#SelectCancel'),
//                        baseZ: 99999
//                    });
//                    return false;
//             }); //termina aca toda la funcion del boton cancel
//
//            $('#cancel2').click(function (){
//                setTimeout($.unblockUI, 200);
//            });
//
//           //Para confirmar la cancelacion de la cita asignada
//
//
//        $('#appConfirm').click(function () {
//                var valbtn = $('#date-search').val();
//                var sala = $('#room-search').val();
//                var idScheRu = $('#idScheRu').val();
//                $('#modalinfo').modal('hide');
//                $.blockUI({css: {
//                    border: 'none',
//                    padding: '15px',
//                    '-webkit-border-radius': '10px',
//                    '-moz-border-radius': '10px',
//                    'border-radius': '10px',
//                    color: '#fff'
//                },
//                    message: '<h3>'+confCita+'</h3><br>\n\
//                        <textarea name="coment_conf" id="coment_conf" placeholder="'+comentConf+'" style="width: 90%;" rows="4"  ></textarea><br>\n\
//                        <center><button class="btn btn-primary" id="acept3" value="CONFIRMACION" style="margin:20px 5px; " >'+si+'</button><button class="btn btn-primary" id="cancel3" >'+no+'</button></center>',
//                    baseZ: 88888
//                });
//                $('#acept3').click(function () {
//
//                    var comment = $('#coment_conf').prop('value');
//                    var action = $(this).prop('value');
//                    if (comment == '') {
//                        $('#coment_conf').addClass('error');
//                    } else {
//                        $.blockUI({ css: {
//                        border: 'none',
//                        padding: '15px',
//                        '-webkit-border-radius': '10px',
//                        '-moz-border-radius': '10px',
//                        'border-radius': '10px',
//                        color: '#fff'
//
//                    },message:'<h3>'+msjConfi+'<br><img style="width: 40px;" src="'+SrcGif+'" style="border: none;"/> '+wait+'......' , baseZ: 99999 });
//
//
//                        $.ajax({
//                            url: UrlScheduleOption,
//                            type: 'POST',
//                            data: {'idSche': idSche, 'descrip': comment, 'action': action, 'type': 'CF'},
//                            success: function (datac) {
//                                var obj = JSON.parse(datac);
//                                console.log(obj);
//
//                                $.blockUI({css: {
//                                        border: 'none',
//                                        padding: '15px',
//                                        '-webkit-border-radius': '10px',
//                                        '-moz-border-radius': '10px',
//                                        'border-radius': '10px',
//                                        color: '#fff'
//                                    },
//                                    message: '<div class="alert alert-success" role="alert" style="font-size:30px; margin:0; padding:10px;" > <span class="glyphicon glyphicon-alert" aria-hidden="true"></span> <span class="sr-only">Error:</span>'+processOk+'</div>'});
//
//                                if (user != '') {
//                                    getDisponibilityUser(sala, valbtn, user, idScheRu,day);
//                                } else {
//                                    getDisponibility(sala, valbtn, '');
//                                }
//                                $('#modalinfo').modal('hide');
//                                //setTimeout($.unblockUI, 3000);
//                            },
//                            onFailure: function () {
//                                $('#divalert').toggle('slow');
//                                setTimeout(function () {
//                                    $('#divalert').toggle('slow');
//                                    $('#modal').modal('toggle');
//                                }, 3000);
//                            }
//                        })
//                        //setTimeout($.unblockUI, 200);
//                        return false;
//                    }
//                });
//
//                $('#cancel3').click(function () {
//                    setTimeout($.unblockUI, 200);
//                });
//            });
//
//
//
//
//        }
//        }
//
//
//});
//
//
//
//
//}
//
//$('#appComments').click(function(){
//                var comentario = $('#comentario').val();
//                var idSche = $(this).attr('value');
//                var user = $('#idUser').prop('value');
//                $.blockUI({ css: {
//                        border: 'none',
//                        padding: '15px',
//                        '-webkit-border-radius': '10px',
//                        '-moz-border-radius': '10px',
//                        'border-radius': '10px',
//                        color: '#fff'
//
//                    },message:'<h3><img style="width: 40px;" src="'+SrcGif+'" style="border: none;"/>'+addComent+'...' , baseZ: 99999 });
//                $.ajax({
//                    url: UrlScheduleOption,
//                    type: 'POST',
//                    data: {'idSche': idSche,'descrip':comentario, 'action':'ASIGNACION', 'type': 'addC'},
//                    success: function(datacm){
//                        var obj = JSON.parse(datacm);
//                        console.log(obj);
//                        if(obj == ''){
//                            $.blockUI({css: {
//                                border: 'none',
//                                padding: '15px',
//                                '-webkit-border-radius': '10px',
//                                '-moz-border-radius': '10px',
//                                'border-radius': '10px',
//                                color: '#fff'
//                            },
//                            message: '<div class="alert alert-success" role="alert" style="font-size:30px; margin:0; padding:10px;" > <span class="glyphicon glyphicon-alert" aria-hidden="true"></span> <span class="sr-only">Error:</span>'+processOk+'</div>', baseZ: 99999});
//                            setTimeout(function(){
//                                btnDangerAction(idSche,user,'');
//                                $('#comentario').val('');
//                            }, 2000);
//                        }
//                    }
//                })
//                return false;
//            });
//
//            $('#acept2').click(function () {
//            var valbtn = $('#date-search').val();
//            var sala = $('#room-search').val();
//            var idScheRu = $('#idScheRu').val();
//            var commentSel = $('#commentCancel').val();
//            var commentTxt = $('#commentsCancelTxt').val();
//            var comment = commentSel+' - '+commentTxt;
//            var action = $(this).prop('value');
//            var idSche = $('#idSche').prop('value');
//            var user = $('#idUser').prop('value');
//
//            if (comment == '') {
////                $('#commentCancel').addClass('error');
//                $('[data-id="commentCancel"]').removeClass('btn-primary').addClass('btn-danger');
//            } else {
//                $.blockUI({ css: {
//                border: 'none',
//                padding: '15px',
//                '-webkit-border-radius': '10px',
//                '-moz-border-radius': '10px',
//                'border-radius': '10px',
//                color: '#fff'
//
//            },message:'<h3>'+cancelCita+'<br><img style="width: 40px;" src="'+SrcGif+'" style="border: none;"/> '+wait+'......' , baseZ: 99999 });
//                $.ajax({
//                    url: UrlScheduleOption,
//                    type: 'POST',
//                    data: {'idSche': idSche, 'descrip': comment, 'action': action, 'type': 'C'},
//                    cache: false,
//                    success: function (data) {
//                        var obj = JSON.parse(data);
//                        console.log(obj);
//                        if (obj == 2) {
//                            $.blockUI({css: {
//                                border: 'none',
//                                padding: '15px',
//                                '-webkit-border-radius': '10px',
//                                '-moz-border-radius': '10px',
//                                'border-radius': '10px',
//                                color: '#fff'
//                            },
//                            message: '<div class="alert alert-danger" role="alert" style="font-size:30px; margin:0; padding:10px;" > <span class="glyphicon glyphicon-alert" aria-hidden="true"></span> <span class="sr-only">Error:</span>'+citaRecepcionada+'</div>'});
//                           setTimeout($.unblockUI, 3000);
//                        }else{
//                            if (user != '') {
//                                getDisponibilityUser(sala, valbtn, user, idScheRu,day);
//                            } else {
//                                getDisponibility(sala, valbtn, '');
//                            }
//                            $.blockUI({css: {
//                                border: 'none',
//                                padding: '15px',
//                                '-webkit-border-radius': '10px',
//                                '-moz-border-radius': '10px',
//                                'border-radius': '10px',
//                                color: '#fff'
//                            },
//                            message: '<div class="alert alert-success" role="alert" style="font-size:30px; margin:0; padding:10px;" > <span class="glyphicon glyphicon-alert" aria-hidden="true"></span> <span class="sr-only">Error:</span>'+processOk+'</div>'});
//                            $('.selectpicker2').selectpicker('deselectAll');
//                            //setTimeout($.unblockUI, 3000);
//                        }
//                       $('#modalinfo').modal('hide');
//                       $('#commentCancel').prop('selectedIndex',0);
//                       $('#commentsCancelTxt').val('');
//                    }
//                })
////                setTimeout($.unblockUI, 200);
//                return false;
//            }
//        });
//
//        $('.selectpicker2').change(function(){
//            $('[data-id="commentCancel"]').removeClass('btn-danger').addClass('btn-primary');
//        });
//
//
//
////---------------------------funcion para dar formato de fecha-------------------------------------------
//
//
//var dateFormat = function () {
//	var	token = /d{1,4}|m{1,4}|yy(?:yy)?|([HhMsTt])\1?|[LloSZ]|"[^"]*"|'[^']*'/g,
//		timezone = /\b(?:[PMCEA][SDP]T|(?:Pacific|Mountain|Central|Eastern|Atlantic) (?:Standard|Daylight|Prevailing) Time|(?:GMT|UTC)(?:[-+]\d{4})?)\b/g,
//		timezoneClip = /[^-+\dA-Z]/g,
//		pad = function (val, len) {
//			val = String(val);
//			len = len || 2;
//			while (val.length < len) val = "0" + val;
//			return val;
//		};
//
//	// Regexes and supporting functions are cached through closure
//	return function (date, mask, utc) {
//		var dF = dateFormat;
//
//		// You can't provide utc if you skip other args (use the "UTC:" mask prefix)
//		if (arguments.length == 1 && Object.prototype.toString.call(date) == "[object String]" && !/\d/.test(date)) {
//			mask = date;
//			date = undefined;
//		}
//
//		// Passing date through Date applies Date.parse, if necessary
//		date = date ? new Date(date) : new Date;
//		if (isNaN(date)) throw SyntaxError("invalid date");
//
//		mask = String(dF.masks[mask] || mask || dF.masks["default"]);
//
//		// Allow setting the utc argument via the mask
//		if (mask.slice(0, 4) == "UTC:") {
//			mask = mask.slice(4);
//			utc = true;
//		}
//
//		var	_ = utc ? "getUTC" : "get",
//			d = date[_ + "Date"](),
//			D = date[_ + "Day"](),
//			m = date[_ + "Month"](),
//			y = date[_ + "FullYear"](),
//			H = date[_ + "Hours"](),
//			M = date[_ + "Minutes"](),
//			s = date[_ + "Seconds"](),
//			L = date[_ + "Milliseconds"](),
//			o = utc ? 0 : date.getTimezoneOffset(),
//			flags = {
//				d:    d,
//				dd:   pad(d),
//				ddd:  dF.i18n.dayNames[D],
//				dddd: dF.i18n.dayNames[D + 7],
//				m:    m + 1,
//				mm:   pad(m + 1),
//				mmm:  dF.i18n.monthNames[m],
//				mmmm: dF.i18n.monthNames[m + 12],
//				yy:   String(y).slice(2),
//				yyyy: y,
//				h:    H % 12 || 12,
//				hh:   pad(H % 12 || 12),
//				H:    H,
//				HH:   pad(H),
//				M:    M,
//				MM:   pad(M),
//				s:    s,
//				ss:   pad(s),
//				l:    pad(L, 3),
//				L:    pad(L > 99 ? Math.round(L / 10) : L),
//				t:    H < 12 ? "a"  : "p",
//				tt:   H < 12 ? "am" : "pm",
//				T:    H < 12 ? "A"  : "P",
//				TT:   H < 12 ? "AM" : "PM",
//				Z:    utc ? "UTC" : (String(date).match(timezone) || [""]).pop().replace(timezoneClip, ""),
//				o:    (o > 0 ? "-" : "+") + pad(Math.floor(Math.abs(o) / 60) * 100 + Math.abs(o) % 60, 4),
//				S:    ["th", "st", "nd", "rd"][d % 10 > 3 ? 0 : (d % 100 - d % 10 != 10) * d % 10]
//			};
//
//		return mask.replace(token, function ($0) {
//			return $0 in flags ? flags[$0] : $0.slice(1, $0.length - 1);
//		});
//	};
//}();
//
//// Some common format strings
//dateFormat.masks = {
//	"default":      "ddd mmm dd yyyy HH:MM:ss",
//	shortDate:      "m/d/yy",
//	mediumDate:     "mmm d, yyyy",
//	longDate:       "mmmm d, yyyy",
//	fullDate:       "dddd, mmmm d, yyyy",
//	shortTime:      "h:MM TT",
//	mediumTime:     "h:MM:ss TT",
//	longTime:       "h:MM:ss TT Z",
//	isoDate:        "yyyy-mm-dd",
//	isoTime:        "HH:MM:ss",
//	isoDateTime:    "yyyy-mm-dd'T'HH:MM:ss",
//	isoUtcDateTime: "UTC:yyyy-mm-dd'T'HH:MM:ss'Z'"
//};
//
//// Internationalization strings
//dateFormat.i18n = {
//	dayNames: [
//		"Sun", "Mon", "Tue", "Wed", "Thu", "Fri", "Sat",
//		"Sunday", "Lunes", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday"
//	],
//	monthNames: [
//		"Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec",
//		"January", "February", "March", "April", "May", "June", "Julio", "August", "September", "October", "November", "December"
//	]
//};
//
//// For convenience...
//Date.prototype.format = function (mask, utc) {
//	return dateFormat(this, mask, utc);
//};
//
//
//function  changeDataDocument(valor) {
//
//
//    var docum = valor.split(" - ");
//    var documento = docum[0];
//
//
//
//    var firstLastName = $.trim($('#firstLastName').val());
//    var secondLastName = $.trim($('#secondLastName').val());
//    var firstName = $.trim($('#firstName').val());
//    var secondName = $.trim($('#secondName').val());
//    var gender = $.trim($('#gender').val());
//    var birthDate = $('#birthDate').val();
//    var gs_rh = $('#gs_rh').val();
//    var doc = '';
////    var address = $('#adress').val();
////    var contactName = $('#contactName').val();
//    if (documento != '') {
//
//            doc = documento;
//            $('#tags').val(doc);
//
//
//            getPatient(doc,1);
//
//    }
//}
//
//function getPatient(documento, id_type) {
//    $.ajax({
//        url: UrlDataPatient,
//        type: "POST",
//        data: {
//            'documento': documento
//        },
//        success: function (resp) {
//            var jsonResp = JSON.parse(resp);
//            //$("#municipalityName").empty();
//
//            console.log(jsonResp);
//            if(jsonResp == 0){
//                $.blockUI({css: {
//                    border: 'none',
//                    padding: '15px',
//                    '-webkit-border-radius': '10px',
//                    '-moz-border-radius': '10px',
//                    'border-radius': '10px',
//                    color: '#fff'
//
//                },
//                   message:'<div class="alert alert-danger" role="alert" style="font-size:20px; margin:0; padding:10px;" > <span class="glyphicon glyphicon-alert" aria-hidden="true"></span> <span class="sr-only">Error:</span>'+noPatient+'</div>',
//                    baseZ: 88888
//                });
//                //$('#tags').val('');
//                 setTimeout($.unblockUI, 3000);
//                 $('#btn-save').hide('slow');
//            }else{
//                //for (var i = 0; i < jsonResp.length; i++) {
//
//                        $("#email").val(jsonResp[0].email);
//                        $("#cellphone").val(jsonResp[0].cellphone);
//                        $("#telephone").val(jsonResp[0].telephone);
//                        $("#address").val(jsonResp[0].address);
//                        if(jsonResp[0].weight ==''){
//                            $("#weight").val(jsonResp[0].weight);
//                        }else{
//                            $("#weight").val(0);
//                        }
//                        var name = documento+' - '+jsonResp[0].firstName+' '+jsonResp[0].secondName+' '+jsonResp[0].firstLastName+' '+jsonResp[0].secondLastName;
//                        $('#tags').val(name);
//
//                //}
//                $('#btn-save').show('fast');
//                $.unblockUI();
//            }
//        },
//        onFailure: function () {
//            alert('Se ha producido un error');
//        }
//    })
//    return false;
//}
//
//    $('#SeeLocks').click( function () {
//        $.blockUI({
//            css: {
//                border: 'none',
//                padding: '15px',
//                '-webkit-border-radius': '10px',
//                '-moz-border-radius': '10px',
//                'border-radius': '10px',
//                color: '#fff'
//            },
//            message: '<h3><img style="width: 40px;" src="' + SrcGif + '" style="border: none;"/> '+wait+'...',
//            baseZ: 99999
//        });
//
//        $.ajax({
//            url: UrlBlockRoom,
//            type: 'POST',
//            data: { 'id': RoomId },
//            success: function (result) {
//                $('#SeeLocksRoomDataAg').html('');
//                var jsonResp = JSON.parse(result);
//                $.unblockUI();
//                $('#SeeLocksRoom').html(RoomName);
//                $('#SeeLocksModal').modal();
//                $('#SeeLocksRoomData').html(jsonResp.table);
//            }
//        });
//    });
//
//    $(document).on('click', '#searchDataAg', function () {
//        var idAg = $(this).data('id');
//        $('#SeeLocksRoomDataAg').html('');
//
//        $.blockUI({
//            css: {
//                border: 'none',
//                padding: '15px',
//                '-webkit-border-radius': '10px',
//                '-moz-border-radius': '10px',
//                'border-radius': '10px',
//                color: '#fff'
//            },
//            message: '<h3><img style="width: 40px;" src="' + SrcGif + '" style="border: none;"/> '+wait+'...',
//            baseZ: 99999
//        });
//
//        $.ajax({
//            url: UrlSearchAg,
//            type: 'POST',
//            data: { 'id': idAg },
//            success: function (result) {
//                var jsonResp = JSON.parse(result);
//                $.unblockUI();
//                $('#SeeLocksRoomDataAg').html(jsonResp.data);
//            }
//        });
//    });
//
//    $('#document').keyup(function (e) {
//        if (e.keyCode == 13) {
//            $( "#searchDate" ).trigger( "click" );
//            e.preventDefault();
//            return false;
//        }
//    });
//
//    // Buscar citas de un paciente
//    $('#searchDate').click( function () {
//        var doc = $('#document').val();
//        searchHistory(doc, true);
//    });
//    // Buscar citas de un paciente al momento de asignar
//    $('#searchPatientList').click( function () {
//        var doc = $('#tags').val();
//        searchHistory(doc, false);
//    });
//    // Borrar tabla al cerrar o asígnar una cita
//    $('#btn-close, #btn-save').click( function () {
//        $('#consultaAgenda2').html('');
//        $('#tituloHistorial').hide();
//    });
//
//    /**
//     * Función que hace la busqueda del historial de citas del paciente
//     *
//     * @param doc
//     * @param change
//     * @returns {boolean}
//     */
//    function searchHistory(doc, change) {
//        $.blockUI({
//            css: {
//                border: 'none',
//                padding: '15px',
//                '-webkit-border-radius': '10px',
//                '-moz-border-radius': '10px',
//                'border-radius': '10px',
//                color: '#fff'
//            },
//            message: '<div ><h3 ><img style="width: 40px; border: none;" src="' + SrcGif + '"/>'+wait+'... </h3> </div>',
//            baseZ: 99999
//        });
//        // esta funcion fue reciclada del modulo de consultar agenda y por este motivo las variables del ajax no se pueden renombrar. datafilter contiene el documento que se desea consultar, los demás datafilter estarán vacios
//        //date_init y date_end tendran por defecto un valor 0 para hacer una validaqción en el controler y consultar los siguientes 3 meses a partir de la fecha de consulta.
//
//        // var doc = $('#document').val();
//        var date_init = 0;
//        var date_end = 0;
//
//        if (doc == '') {
//            $('#document').addClass('error');
//            $('.error').change( function () {
//                $(this).removeClass('error');
//            });
//            $.blockUI({
//                css: {
//                    border: 'none',
//                    padding: '15px',
//                    '-webkit-border-radius': '10px',
//                    '-moz-border-radius': '10px',
//                    'border-radius': '10px',
//                    color: '#fff'
//                },
//                message: '<h4><span class="glyphicon glyphicon-alert text-danger" style="font-size:40px;" ></span></h4><h4>'+docSearch+'</h4>',
//                baseZ: 99999
//            });
//            setTimeout($.unblockUI, 3500);
//        } else {
//            $('#document').removeClass('error');
//            $.ajax({
//                url: UrlConsultWL,
//                type: 'POST',
//                data: {
//                    datafilter: doc,
//                    datafilter2: '',
//                    datafilter3: '',
//                    datafilter4: '',
//                    datafilter5: '',
//                    dateInit: date_init,
//                    dateEnd: date_end,
//                    filter: 'per.document',
//                    searchDate: 'true'
//                },
//                success: function (data) {
//                    var obj = JSON.parse(data);
//                    console.log(obj);
//                    if (obj == 0) {
//                        $.blockUI({
//                            css: {
//                                border: 'none',
//                                padding: '15px',
//                                '-webkit-border-radius': '10px',
//                                '-moz-border-radius': '10px',
//                                'border-radius': '10px',
//                                color: '#fff'
//                            },
//                            message: '<div class="alert alert-danger" role="alert" style="font-size:20px; margin:0; padding:10px;" > <span class="glyphicon glyphicon-alert" aria-hidden="true" style="font-size:40px;" ></span><br><span class="sr-only">Error:</span>'+cita6meses+'<br><button class="btn btn-danger2" id="cerrar1" >'+acepta+'</button></div>',
//                            baseZ: 99999
//                        });
//                        // setTimeout($.unblockUI, 3000);
//                        $('#cerrar1').click( function () {
//                            $.unblockUI();
//                        });
//                    } else {
//                        if (obj.contRow == 0) {
//                            $.blockUI({
//                                css: {
//                                    border: 'none',
//                                    padding: '15px',
//                                    '-webkit-border-radius': '10px',
//                                    '-moz-border-radius': '10px',
//                                    'border-radius': '10px',
//                                    color: '#fff'
//                                },
//                                message: '<div class="alert alert-danger" role="alert" style="font-size:20px; margin:0; padding:10px;" > <span class="glyphicon glyphicon-alert" aria-hidden="true" style="font-size:40px;" ></span><br><span class="sr-only">Error:</span>'+cita6meses+'<br><button class="btn btn-danger2" id="cerrar2" >'+acepta+'</button> </div>',
//                                baseZ: 99999
//                            });
//                            // setTimeout($.unblockUI, 3000);
//                            $('#cerrar2').click( function () {
//                                $.unblockUI();
//                            });
//                        } else {
//                            $('#consultaAgenda').html('');
//                            $('#consultaAgenda2').html('');
//                            if (change) {
//                                $('#datosPaciente').html('');
//                                $('#datosPaciente').html(obj.paciente);
//
//                                // $('#consultaAgenda').html('');
//                                $('#consultaAgenda').html(obj.data);
//
//                                // $('.accionCitaCancel').addClass('hide');
//                                // $('.Oculto').addClass('hide');
//                                $('#consultAgenda').modal();
//                            } else {
//                                // $('#consultaAgenda2').html('');
//                                $('#tituloHistorial').show();
//                                $('#consultaAgenda2').html(obj.data);
//                                $('.accionCita').removeClass();
//                            }
//                            $('.accionCitaCancel').addClass('hide');
//                            $('.Oculto').addClass('hide');
//                            $.unblockUI();
//                            /*
//                             data table inicio
//                             $('#example tfoot th').each( function () {
//                             var title = $('#example thead th').eq( $(this).index() ).text();
//                             $(this).html( '<input type="text" />' );
//                             } );
//                             */
//
//                            // DataTable
//                            var table = $('#example').DataTable({
//                                    language: {
//                                        search:         search,
//                                        lengthMenu:     lengthMenu,
//                                        info:           info,
//                                        infoFiltered:   infoFiltered,
//                                        loadingRecords: loadingRecords,
//                                        zeroRecords:    zeroRecords,
//                                        emptyTable:     emptyTable,
//                                        paginate: {
//                                        first:      first,
//                                        previous:   previous,
//                                        next:       next,
//                                        last:       last
//                                        }
//                                    }
//                                });
//                            /*
//                             // Apply the search
//                             table.columns().every( function () {
//                             var that = this;
//
//                             $( 'input', this.footer() ).on( 'keyup change', function () {
//                             that
//                             .search( this.value )
//                             .draw();
//                             } );
//                             } );
//                             */
//                            //data table fin
//
//                            $('#example').on( 'click', '.accionCita', function () {
//                                $.blockUI({
//                                    css: {
//                                        border: 'none',
//                                        padding: '15px',
//                                        '-webkit-border-radius': '10px',
//                                        '-moz-border-radius': '10px',
//                                        'border-radius': '10px',
//                                        color: '#fff'
//                                    },
//                                    message: '<div ><h3 ><img style="width: 40px; border: none;" src="' + SrcGif + '"/>'+wait+'... </h3> </div>',
//                                    baseZ: 99999
//                                });
//                                var roomCode = $(this).data('roomcode');
//                                var fecha = $(this).data('fechac');
//                                $('#room-searchUpdate').val(roomCode);
//                                $('#room-search').val(roomCode);
//                                $('#date-search').val(fecha);
//                                $('#date-searchUpdate').val(fecha);
//                                $('#document').val('');
//
//                                var idSche = $(this).data('idschecons');
//                                $('#idSche').attr('value', idSche);
//                                btnDangerAction(idSche, '', '');
////                                getDisponibility(roomCode, fecha, '');
////                                setTimeout($.unblockUI, 3500);
////                                $(".salas[value='" + roomCode + "']").trigger("click");
//                            });
//                        }
//                    }
//                }
//            });
//            return false;
//        }
//    }
//
//    //
//
//});
//
//function printInidcations(indica,contrain,prein){
//    $.ajax({
//            url: UrlStudyIndications,
//            type: 'POST',
//            data: {'indicacion': indica, 'contraindicacion':contrain,'preindicacion':prein},
//            success: function(data){
//
//
//            }
//        });
//
//}
