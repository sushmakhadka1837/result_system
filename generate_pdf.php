require 'vendor/autoload.php';
use Dompdf\Dompdf;

$dompdf = new Dompdf();
$html = "<h2>Student Result</h2><table>..."; // generate result HTML
$dompdf->loadHtml($html);
$dompdf->render();
$dompdf->stream("result.pdf");
