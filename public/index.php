<?php
    require_once __DIR__.'/../vendor/autoload.php';
    require_once __DIR__.'/../.env.php';

    $app = new Silex\Application();
    $dbc = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME, DB_USER, DB_PASS);
    $dbc->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $app['debug'] = true;

    $app->register(new Silex\Provider\TwigServiceProvider(), array(
    'twig.path' => __DIR__ . '/views'));

    $app->get('/', function() use ($app, $dbc){

        $createTable = "CREATE TABLE IF NOT EXISTS " . TABLE . " (
                        `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
                        `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
                        `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
                        `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
                        `is_complete` tinyint(1) NOT NULL DEFAULT '0',
                        PRIMARY KEY (`id`))";

        $dbc->exec($createTable);

        $query = 'SELECT * FROM ' . TABLE . ' ORDER BY id DESC';
        $tasks = $dbc->query($query);

        return $app['twig']->render('view.twig', ['tasks'=> $tasks]);

    });


    $app->post('/tasks', function() use ($app, $dbc) {

        if(empty($_POST['name'])) {
            return $app->redirect('/');
        }

        $name        = $_POST['name'];
        $is_complete = false;
        $now         = date("Y-m-d H:i:s");

        $query = 'INSERT INTO ' . TABLE . ' (name, is_complete, created_at, updated_at)
                  VALUES (?,?,?,?)';
        $stmt = $dbc->prepare($query);
        $stmt->execute(array($name, $is_complete, $now, $now));

        return $app->redirect('/');
        });

    $app->post('/tasks/{id}/toggle-complete', function($id) use ($app, $dbc) {

        $query = "SELECT is_complete FROM " . TABLE . " WHERE id = :id";
        $stmt  = $dbc->prepare($query);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        $task = $stmt->fetch(PDO::FETCH_ASSOC);

        $is_complete = !$task['is_complete'];
        $updated_at  = date("Y-m-d H:i:s");

        $query = "UPDATE " . TABLE . " SET is_complete = :is_complete, updated_at = :updated_at WHERE id = :id";
        $stmt  = $dbc->prepare($query);
        $stmt->bindValue(':id',          $id,          PDO::PARAM_INT);
        $stmt->bindValue(':is_complete', $is_complete, PDO::PARAM_INT);
        $stmt->bindValue(':updated_at',  $updated_at,  PDO::PARAM_STR);
        $stmt->execute();

        return $app->redirect('/');

    });

    $app->post('/tasks/clear-complete', function() use ($app, $dbc) {

        $stmt             = $dbc->query('SELECT id
                                         FROM ' . TABLE .
                                         ' WHERE is_complete = TRUE');

        $tasksToBeRemoved = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach($tasksToBeRemoved as $task)
        {
            $dbc->exec("DELETE FROM tasks WHERE id = " . $task['id']);
        }

       return $app->redirect('/');

    });

    $app->run();

?>
