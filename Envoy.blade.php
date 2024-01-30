
@servers([
    'web' => ['-i /Users/rupadana/Freelance/app.codecrafters.id/id_rsa homenet-app@homenet.id'],
    'vm' => ['homenet-vm@panel.middacorp.net'],
    'inventory' => ['homenet-inventory@homenet.id']
])

@task('restart-queues', ['on' => 'web'])
    cd /home/homenet-app/htdocs/app.homenet.id
    php artisan queue:restart
@endtask

@task('deploy-vm', ['on' => 'vm'])
    cd /home/homenet-vm/htdocs/vm.homenet.id
    git pull
@endtask

@task('deploy-inventory', ['on' => 'inventory'])
    cd /home/homenet-inventory/htdocs/inventory.homenet.id
    git pull
@endtask
