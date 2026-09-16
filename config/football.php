<?php
return ['provider'=>env('FOOTBALL_PROVIDER','api-football'),'api_football'=>['base_url'=>env('API_FOOTBALL_BASE_URL','https://v3.football.api-sports.io'),'key'=>env('API_FOOTBALL_KEY'),'timeout'=>15],'thresholds'=>['strong'=>85,'qualified'=>80,'watch'=>70,'minimum_data_quality'=>55]];
