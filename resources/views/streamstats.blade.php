<html>
<head>
    <meta charset="utf-8">
    <title>StreamStats</title>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <style>
        .button {
            font: bold 15px Arial;
            text-decoration: none;
            background-color: #EEEEEE;
            color: #333333;
            padding: 2px 6px 2px 6px;
            border-top: 1px solid #CCCCCC;
            border-right: 1px solid #333333;
            border-bottom: 1px solid #333333;
            border-left: 1px solid #CCCCCC;
        }

        #sortable thead th.desc:after {
            content: '↑';
        }

        #sortable thead th.asc:after {
            content: '↓';
        }
    </style>

    <meta name="csrf-token" content="{{ csrf_token() }}" />

</head>
<body>

    <h1>StreamStats</h1>

    <div>
        <a href="" id="authorize_twitch"  class="button">Login</a>
    </div>
    <br />
    <hr />

    <div id="info">
        Your Access Key from the #url:  <span id="access_token"> access_token</span><br />
        User Id:  <span id="user_id"> access_token</span><br />
    </div>
    <hr />
    
    <h3>Total number of streams for each game</h3>
    <div style="overflow:scroll; height:200px;">
        <table>
            <tr>
                <th>Game Name</th>
                <th>Count</th>
            </tr>
            @foreach ($streams_per_game as $row)
            <tr>
                <td>{{ $row->game_name }}</td>
                <td>{{ $row->count }}</td>
            </tr>
            @endforeach
        </table>
    </div>
    <hr />

    <h3>Top games by viewer count for each game</h3>
    <div style="overflow:scroll; height:200px;">
        <table>
            <tr>
                <th>Game Name</th>
                <th>Count</th>
            </tr>
            @foreach ($top_games_per_game as $row)
            <tr>
                <td>{{ $row->game_name }}</td>
                <td>{{ $row->count }}</td>
            </tr>
            @endforeach
        </table>
    </div>
    <hr />
    
    <h3>Median number of viewers for all streams</h3>
    <div>
        {{ $median_number_of_viewers }}
    </div>
    <hr />
    
    <h3>List of top 100 streams by viewer count that can be sorted asc & desc</h3>
    <div style="overflow:scroll; height:200px;">
        <table id="sortable">
            <thead>
                <tr>
                    <th>Game Name</th>
                    <th class="asc" onclick="sortTable(this)">Count</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($top_100_streams as $row)
                <tr>
                    <td>{{ $row->stream_title }}</td>
                    <td>{{ $row->count }}</td>
                </tr>
                @endforeach
            </tbody>    
        </table>​
    </div>
    <hr />


    <h3>Total number of streams by their start time (rounded to the nearest hour)</h3>
    <div style="overflow:scroll; height:200px;">
        <table>
            <thead>
                <tr>
                    <th>Game Name</th>
                    <th>Count</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($number_of_streams_by_hour as $row)
                <tr>
                    <td>{{ $row->hour }}:00:00</td>
                    <td>{{ $row->count }}</td>
                </tr>
                @endforeach
            </tbody>    
        </table>​
    </div>
    <hr />


    <h3>Streams following</h3>
    <div>
        <table id="user_following">
            <tr>
                <th>Channel Name</th>
                <th>Stream Title</th>
            </tr>
        </table>
    </div>
    <hr />

    
    <h3>How many viewers does the lowest viewer count stream that the logged in user is following need to gain in order to make it into the top 1000?</h3>
    <div>
        NA
    </div>
    <hr />

    
    <h3>Which tags are shared between the user followed streams and the top 1000 streams? Also make sure to translate the tags to their respective name?</h3>
    <div>
        <table id="shared_tags">
            <tr>
                <th>Tag Id</th>
                <th>Tag Name</th>
            </tr>
        </table>
    </div>
    <hr />

    <script>
        function sortTable(el){
            var getClass = el.className;
            if (getClass=='desc') {
                desc = true;
                $(el).removeClass('desc').addClass('asc');
            } else {
                desc = false;
                $(el).removeClass('asc').addClass('desc');
            }

            var rows = $('#sortable tbody tr').get();

            rows.sort(function(a, b) {
                var A = parseInt( $(a).children('td').eq(1).text() );
                var B = parseInt( $(b).children('td').eq(1).text() );
                if (desc) {
                    let C = A;
                    A = B;
                    B = C;
                }

                if(A < B) {
                    return -1;
                }

                if(A > B) {
                    return 1;
                }

                return 0;

            });

            $.each(rows, function(index, row) {
                $('#sortable').children('tbody').append(row);
            });
        }
    </script> 

    <script type="text/javascript">
        var client_id = '{{$client_id}}';
        var app_url = '{{$app_url}}';

        document.getElementById('authorize_twitch').setAttribute('href', 'https://id.twitch.tv/oauth2/authorize?client_id=' + client_id 
            + '&redirect_uri=' + encodeURIComponent(app_url)
            + '&response_type=token' 
            + '&scope=user:read:email%20user:read:follows');
        document.getElementById('access_token').textContent = '';

        var user_id = null;
        if (document.location.hash && document.location.hash != '') {
            var parsedHash = new URLSearchParams(window.location.hash.substr(1));
            if (parsedHash.get('access_token')) {
                var access_token = parsedHash.get('access_token');
                $('#access_token').html(access_token);

                $.ajax({
                    url: 'https://api.twitch.tv/helix/users',
                    type: 'GET',
                    dataType: 'json',
                    headers: {
                        "Client-ID": client_id,
                        "Authorization": "Bearer " + access_token
                    },
                    contentType: 'application/json; charset=utf-8',
                    success: function (resp) {
                        var data = resp.data[0];
                        user_id = data['id'];
                        $('#user_id').html(user_id);

                        userFollowedStreams();
                        sharedTags();

                    },
                    error: function (error) {
                        console.log(err);
                        alert('Something went wrong');
                    }
                });

            }
        } else if (document.location.search && document.location.search != '') {
            var parsedParams = new URLSearchParams(window.location.search);
            if (parsedParams.get('error_description')) {
                document.getElementById('access_token').textContent = parsedParams.get('error') + ' - ' + parsedParams.get('error_description');
            }
        }

        function userFollowedStreams() {
            $.ajax({
                url: 'https://api.twitch.tv/helix/streams/followed?user_id=' + user_id,
                type: 'GET',
                dataType: 'json',
                headers: {
                    "Client-ID": client_id,
                    "Authorization": "Bearer " + access_token
                },
                contentType: 'application/json; charset=utf-8',
                success: function (resp) {
                    var streams = resp.data;
                    streams.forEach(function(stream) {

                        let data = {
                            'user_name' : stream['user_name'],
                            'title' : stream['title']
                        };
                        $.ajax({
                            url:  app_url + '/followed',
                            data : JSON.stringify(data),
                            method: "POST",
                            headers: {
                                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                            },
                            contentType : 'application/json',
                            success: function (data) {
                                if (data) {
                                    $('#user_following').append(
                                        '<tr>'
                                        + '<td>' + stream['user_name'] + '</td>'
                                        + '<td>' + stream['title'] + '</td>'
                                        + '</tr>'
                                    );
                                }
                            },
                            error: function (err) {
                                console.log(err);
                                alert('Something went wrong');
                            }   
                        });


                    });
                },
                error: function (error) {
                    console.log(err);
                    alert('Something went wrong');
                }
            });
        }

        function sharedTags() {
            fetch(
                'https://api.twitch.tv/helix/streams?user_id=' + user_id,
                {
                    "headers": {
                        "Client-ID": client_id,
                        "Authorization": "Bearer " + access_token
                    }
                }
            )
            .then(resp => resp.json())
            .then(resp => {
                console.log(resp);
                let data = resp['data'];
                //shared_tags
                data.forEach(function(stream) {
                    let tag_ids = stream['tag_ids'];

                    tag_ids.forEach(function(tag_id) {
                        let data = {
                            'tag_id' : tag_id,
                        };
                        $.ajax({
                            url:  app_url + '/tag',
                            data : JSON.stringify(data),
                            method: "POST",
                            headers: {
                                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                            },
                            contentType : 'application/json',
                            success: function (data) {
                                if (data) {
                                    $('#shared_tags').append(
                                        '<tr>'
                                        + '<td>' + tag_id + '</td>'
                                        + '<td>' + data + '</td>'
                                        + '</tr>'
                                    );
                                }
                            },
                            error: function (data) {
                                console.log(err);
                                alert('Something went wrong');
                            }   
                        });

                    });
                    console.log(tag_ids);
                });
            })
            .catch(err => {
                console.log(err);
                alert('Something went wrong');
            });
        }

    </script>
</body>
</html>
