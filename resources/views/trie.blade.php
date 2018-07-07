@extends('layouts.layout')
@section('content')
    <div class="container">
        <form onSubmit="return formstop();">
            <div class="row">
                <div class="col">
                    <label for="input">Ngôn ngữ tìm kiếm</label>
                    <input id="input" name="input" type="text" class="form-control" placeholder="Ngôn ngữ lập trình">
                </div>
            </div>
        </form>

        <div class="result">
        </div>
    </div>

@endsection

@section('after-script')
    <script>
        function formstop() {
            return false;
        }
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });
        $('#input').change(function(){
            let input = $('#input').val();


            if(input){
                $.ajax({
                    method: 'POST',
                    url: "trie",
                    data: {input:input},
                    success: function(result){
                        let data = "<div>";

                        data += "<h2>"+result['time']+"</h2>";
                        data += "<ul>";
                        result['data'].forEach((ele) => {
                            data += "<li>"+ele+"</li>";
                        });
                        data += "</ul>";
                        data += "</div>";

                        $('.result').html(data);
                    }
                });
            }
        });

    </script>
@endsection