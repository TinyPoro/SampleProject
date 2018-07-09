@extends('layouts.layout')
@section('content')
    <div class="container">
        <h2>Tìm kiếm ngôn ngữ</h2>
        <form onSubmit="return formstop();">
            <div class="row">
                <div class="col">
                    <label for="input">Prefix</label>
                    <input id="input" name="suggest" type="text" class="form-control" placeholder="Ngôn ngữ lập trình">
                </div>
                <div class="col">
                    <label for="input">Contain</label>
                    <input id="input" name="alternateSuggest" type="text" class="form-control" placeholder="Ngôn ngữ lập trình">
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
        $('input').change(function(){
            let input = $(this).val();
            let name = $(this).attr('name');
console.log(input);
console.log(name);
            if(input){
                $.ajax({
                    method: 'POST',
                    url: "trie",
                    data: {input:input, name:name},
                    success: function(result){
                        let data = "<div>";
                        console.log(result);
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