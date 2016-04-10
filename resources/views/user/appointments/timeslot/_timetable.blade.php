@section('css')
@parent
<link rel="stylesheet" href="{{ asset('css/datetime.css') }}">
<style>
.datepicker table tr td.disabled, .datepicker table tr td.disabled:hover {
    color: #999;
    cursor: default;
    border-radius: 0;
}
</style>
@endsection

<div id="catalog">
@if($business->services->count() > 1)
    @if($business->services->count() > 10)
    <input id="filter" name="filter" class="form-control" value="" />
    @endif
<div id="searchlist" class="list-group">
    @foreach ($business->services as $service)
    <a class="list-group-item service-selector" data-service-id="{{ $service->id }}" href="#">
        <span>{{ $service->name }}</span>
        @if($service->duration)
        <span class="text-muted pull-right">({{ trans_duration("{$service->duration} minutes") }})
        @endif
        @if($service->color)
        &nbsp;&nbsp;<i style="background:{{ $service->color }}" class="badge">&nbsp;</i>
        @endif
        </span>
    </a>
    @endforeach
</div>
@endif
</div>

<ul class="list-group">
@foreach ($business->services as $service)
@if($service->description)
    <li class="list-group-item service-description hidden" id="service-description-{{$service->id}}">
    {!! Markdown::convertToHtml($service->description) !!}
    </li>
@endif

@if($service->prerequisites)
<li class="list-group-item service-prerequisites hidden" id="service-prerequisites-{{$service->id}}">
    {!! Markdown::convertToHtml($service->prerequisites) !!}
</li>
@endif
@endforeach

<li class="list-group-item hidden" id="recap-service"></li>
<li class="list-group-item hidden" id="recap-date"></li>
<li class="list-group-item hidden" id="recap-time"></li>

</ul>

<div class="form-group">
    <div class="row">
        <div class="col-md-12">
            <div id="datepicker" class="hide"></div>
        </div>
    </div>
</div>

@section('footer_scripts')
@parent
<script src="{{ asset('js/datetime.js') }}"></script>
<script type="text/javascript">
$(document).ready(function() {

function arr_diff (a1, a2) {

    var a = [], diff = [];

    for (var i = 0; i < a1.length; i++) {
        a[a1[i]] = true;
    }

    for (var i = 0; i < a2.length; i++) {
        if (a[a2[i]]) {
            delete a[a2[i]];
        } else {
            a[a2[i]] = true;
        }
    }

    for (var k in a) {
        diff.push(k);
    }

    return diff;
};

function getDateRange(startDate, endDate, dateFormat) {
    var dates = [],
        end = moment(endDate),
        diff = endDate.diff(startDate, 'days');

    if(!startDate.isValid() || !endDate.isValid() || diff <= 0) {
        return;
    }

    for(var i = 0; i < diff; i++) {
        dates.push(end.subtract(1,'d').format(dateFormat));
    }

    return dates;
};

function updateEnabledDates()
{
    var business = $('#business').val();
    var service = $('#service').val();
    var scanDates = getDateRange(moment(timegrid.startDate), moment(timegrid.endDate).add(1,'d'), 'YYYY-MM-DD');

    $.ajax({
        url:'/api/vacancies/' + business + '/' + service,
        type:'GET',
        dataType: 'json',
        success: function( data ) {

            var disabledDates = arr_diff(scanDates, data.dates);

            $('#datepicker').datepicker('setDatesDisabled', disabledDates);
            $('#datepicker').show();

        },
        fail: function ( data ) {
            console.log('Failed to load dates.');
        }
    });
}

    $('#searchlist').btsListFilter('#filter', { itemChild: 'span' });

    $('.service-selector').click(function(e){
        var serviceId = $(this).data('service-id');

        $('#service').val(serviceId);
        $('#catalog').hide();

        $('#service-prerequisites-'+serviceId).removeClass('hidden').show();
        $('#service-description-'+serviceId).removeClass('hidden').show();
        $('#recap-service').removeClass('hidden').html( $(this).html() );

        updateEnabledDates();
    });

    $('#datepicker').datepicker({
        format: 'yyyy-m-d',
        startDate: timegrid.startDate,
        endDate: timegrid.endDate,
        datesDisabled: false,
        inline: true,
        todayHighlight: true,
        daysOfWeekDisabled: '0'
    }).on('changeDate', function(e) {

        var business = $('#business').val();
        var service = $('#service').val();
        var date = $('#date').val();

        var timesSelect = $('#times');
        var durationInput = $('#duration');

        var day = e.date.getDate();
        var month = e.date.getMonth() + 1;
        var year = e.date.getFullYear();

        var date = day + '-' + month + '-' + year;

        $('#date').val( date );
        $('#recap-date').removeClass('hidden').html( date );

        $.ajax({
            url:'/api/vacancies/' + business + '/' + service + '/' + date,
            type:'GET',
            dataType: 'json',
            success: function( data ) {

                $('#datepicker').hide();
                $('#extra').removeClass('hide').show();

                timesSelect.find('option').remove();
                $.each(data.times,function(key, value)
                {
                    timesSelect.append('<option value=' + value + '>' + value + '</option>');
                });
                durationInput.val(data.service.duration);
            },
            fail: function ( data ) {
                durationInput.val(0);
            }
        });

    });

    $('#datepicker').hide().removeClass('hide');

});
</script>
@endsection