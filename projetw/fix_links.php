<?php
$dir = 'C:/4xampp/htdocs/valorys_Copie/views/frontoffice/medecin';
$files = glob($dir . '/*.php');

foreach ($files as $file) {
    if (is_file($file)) {
        $content = file_get_contents($file);
        $newContent = str_replace(
            ['page=medecin_rendezvous', 'page=mes_rendezvous', 'page=medecin_ordonnances', 'page=medecin_disponibilites'],
            ['page=mes_rendez_vous', 'page=mes_rendez_vous', 'page=ordonnances', 'page=disponibilites'],
            $content
        );
        if ($newContent !== $content) {
            file_put_contents($file, $newContent);
            echo "Updated $file\n";
        }
    }
}
echo "Done!\n";
