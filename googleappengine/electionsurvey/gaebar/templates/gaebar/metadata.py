backup = {}
backup['created_at'] = '{{backup.created_at}}'
backup['modified_at'] = '{{backup.modified_at}}'
backup['models'] = [{% for model in backup.ordered_model_list %}'{{model}}', {% endfor %}]
backup['num_rows'] = {{backup.num_rows}}
backup['num_shards'] = {{backup.num_shards}}
backup['key'] = '{{backup.key}}'

backup['shard_row_limits'] = [{% for shard_row_limit in backup.shard_row_limits %}{{shard_row_limit}}, {% endfor %}]