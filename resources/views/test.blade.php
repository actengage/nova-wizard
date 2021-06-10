<html
<body>
    <form method="post" action="#" enctype="multipart/form-data">
        @csrf

        <input type="file" name="test" />

        <input type="text" name="first" value="{{ $request->first }}" />
        <input type="text" name="last" value="{{ $request->last }}" />
        <input type="text" name="email" value="{{ $request->email }}" />

        <button>Submit</button>
    </form>
</html>