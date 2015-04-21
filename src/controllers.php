<?php

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Form\Extension\Core\ChoiceList\ChoiceList;
use Silex\Provider\SwiftmailerServiceProvider as Swiftmailer;

//Request::setTrustedProxies(array('127.0.0.1'));

require('servers.php');
// user:password@host/path/file

/* Met een class
define('FTP_HOST', '192.168.1.88'); // *** Define your host, username, and password
define('FTP_USER', 'UserX');
define('FTP_PASS', 'PassX');
include('ftp_upload.php');  // *** Include the class
$ftpObj = new FTPClient();  // *** Create the FTP object
$ftpObj -> connect(FTP_HOST, FTP_USER, FTP_PASS);  // *** Connect
*/

$app->match('/', function (Request $request) use ($app, $servers) {

    $succes = null;

    // lijst voor keuzes vullen
    $choices['servers'] = array('');
    $choices['namen'] = array('Selecteer een server');
    foreach ($servers as $item) {
        array_push($choices['servers'], $item['server']);
        array_push($choices['namen'], $item['naam']);
    }

    $form = $app['form.factory']
        ->createBuilder('form')
        ->add('attachment', 'file', array(
            'label' => 'Bestand(en)',
            'required' => true,
            'multiple' => true, // true -> multiple files at the same time/ false -> only one file at a time.
            // TODO bestandsnamen gekozen bestanden laten zien
            'attr' => array(),
        ))
        ->add('server', 'choice', array(
            'label' => 'Server',
            'required' => true,
            'choice_list' => new ChoiceList($choices['servers'], $choices['namen']),
            'attr' => array(
                'data-style' => 'btn-default')
        ))
        ->add('name', 'text', array(
            'label' => 'Naam',
            'constraints' => array(new Assert\NotBlank(), new Assert\Length(array('min' => 5))),
            'attr' => array(
                'data-style' => 'btn-default',
                'placeholder' => 'Naam')
        ))
        ->add('email', 'text', array(
            'label' => 'Email',
            'constraints' => array(new Assert\NotBlank(), new Assert\Email()),
            'attr' => array(
                'data-style' => 'btn-default',
                'placeholder' => 'Email')
        ))
        ->add('message', 'textarea', array(
            'label' => 'Bericht',
            'attr' => array('class' => 'tinymce',
                'rows' => 3,
                'data-style' => 'btn-default',
                'placeholder' => 'Bericht'),
            'required' => false,
            'empty_data' => null
        ))
        ->getForm();

    $request = $app['request'];
    $message = 'Kies een bestand om te uploaden.';

    if ($request->isMethod('POST')) {
        $form->bind($request);

        if ($form->isValid()) {
            $data = $form->getData();
            $server = $data['server'];
            $name = filter_var($data['name'], FILTER_SANITIZE_STRING);
            $emailFrom = filter_var($data['email'], FILTER_SANITIZE_EMAIL);
            $emailMessage = filter_var($data['message'], FILTER_SANITIZE_STRING);

            foreach ($servers as $item) {
                try {
                    if ($server == $item['server']) {
                        $ftp_server = $item['server'];
                        $ftp_username = $item['username'];
                        $ftp_userpass = $item['userpass'];
                        $ftp_conn = ftp_connect($ftp_server) or die("Could not connect to $ftp_server"); // ftp_ssl_connect?
                        $login = ftp_login($ftp_conn, $ftp_username, $ftp_userpass);

                        $i = 0; // ivm multiple file-uploads

                        $date = new \DateTime('now');

                        $files = $request->files;
                        foreach ($files as $uploadedFile) {
                            foreach ($uploadedFile['attachment'] as $bestand) {
                                $error = $bestand->getError(); // -> try/catch
                                try {
                                    $filesize = $bestand->getClientSize();
                                    // max file size = ? nu via php.ini 4Mb TODO evt. filesizecheck

                                    $extension = $bestand->guessExtension();
                                    if (!$extension) {
                                        $extension = 'bin';
                                    }
                                    $filename = $date->format('Ymd_Hms') . '_' . $name . '_' . $i . '.' . $extension;

                                    if (ftp_put($ftp_conn, $filename, $bestand, FTP_ASCII)) {
                                        $succes = "~ Het bestand is opgeslagen op {$item['naam']} als $filename ~"; // in popup weergeven?
                                        // echo "<script type='text/javascript'>alert('Het bestand is opgeslagen op {$item['naam']} als $filename');</script>";

                                        // TODO bij upload mail verzenden naar X? met Waar($server), Wie($name & $emailFrom), Wat($filename) & Message($emailMessage)
                                        // Beste X, $name heeft een bestand geüpload naar $server: $emailMessage<br><br>Klik hier ($server . $filename) om te bekijken.

                                        /*
                                        $datum = $date->format('d-m-Y');
                                        $contactEmail = "saskia.bouten@gmail.com";
                                        $bericht = "$name met het e-mailadres $emailFrom stuurde op $datum het volgende bericht:
                                          ____________________________________
                                            $emailMessage
                                          ------------------------------------";

                                        mail($contactEmail, 'ZENDER upload', $bericht, "From: $emailFrom");
                                        */
                                    } else {
                                        echo "$filename kon niet op de server geplaatst worden.";
                                    }

                                    $i++;
                                } catch (Exception $e) {
                                    return $e;
                                }
                            }
                        }
                    }
                } catch (Exception $e) {
                    return $e;
                }
            }
            ftp_close($ftp_conn);
            $message = "Woeptiedoe, het is gelukt!";
        }
    }
    // aanpassen? na POST redirect : return $app->redirect('...');
    // zonder POST : formulier laten zien
    $response = $app['twig']->render(
        'index.html', array(
        'message' => $message,
        'succes' => $succes,
        'form' => $form->createView()
    ));

    return $response;
}, 'GET|POST');

$app->error(function (\Exception $e, $code) use ($app) {
    if ($app['debug']) {
        return;
    }

    // 404.html, or 40x.html, or 4xx.html, or error.html
    $templates = array(
        'errors/' . $code . '.html',
        'errors/' . substr($code, 0, 2) . 'x.html',
        'errors/' . substr($code, 0, 1) . 'xx.html',
        'errors/default.html',
    );

    return new Response($app['twig']->resolveTemplate($templates)->render(array('code' => $code)), $code);
});

// voorbeelden Silex-site

# Example GET Route
$blogPosts = array(
    1 => array(
        'date' => '2011-03-29',
        'author' => 'igorw',
        'title' => 'Using Silex',
        'body' => '...',
    ),
);

$app->get('/blog', function () use ($blogPosts) {
    $output = '';
    foreach ($blogPosts as $post) {
        $output .= $post['title'];
        $output .= '<br />';
    }

    return $output;
});

# Dynamic Routing
$app->get('/blog/{id}', function (Silex\Application $app, $id) use ($blogPosts) {
    if (!isset($blogPosts[$id])) {
        $app->abort(404, "Post $id does not exist.");
    }

    $post = $blogPosts[$id];

    return "<h1>{$post['title']}</h1>" .
    "<p>{$post['body']}</p>";
});

# Example POST Route
$app->post('/feedback', function (Request $request) {
    $message = $request->get('message');
    mail('feedback@yoursite.com', '[YourSite] Feedback', $message);

    return new Response('Thank you for your feedback!', 201);
});