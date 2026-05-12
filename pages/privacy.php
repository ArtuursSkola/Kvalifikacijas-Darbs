<?php
session_start();
require_once __DIR__ . '/../routes/main.php';

$privacyEmail = 'info@homeestate.lv';
$privacyPhone = '+371 2897 2765';
$privacyAddress = 'Liepāja, Latvija';
$policyUpdated = '12.05.2026';

$pageTitle = 'Privātuma politika - HomeEstate';
$extraStyles = ['privacy'];
$bodyClass = 'privacy-page';
include __DIR__ . '/../includes/header.php';
?>

<section class="privacy-hero">
    <div class="privacy-hero__inner">
        <div class="privacy-hero__label">
            <i class="fas fa-shield-halved"></i>
            Datu aizsardzība
        </div>
        <h1>Privātuma <span>politika</span></h1>
        <p class="privacy-hero__meta">Pēdējās izmaiņas: <?php echo htmlspecialchars($policyUpdated, ENT_QUOTES, 'UTF-8'); ?></p>
    </div>
</section>

<div class="privacy-article-wrap">
    <article class="privacy-article" lang="lv">
        <p class="privacy-lead">
            Šī privātuma politika (turpmāk — Politika) izskaidro, kā HomeEstate tiešsaistes platforma (turpmāk — Platforma) apstrādā jūsu personas datus, kad jūs apmeklējat vietni, reģistrējaties, izmantojat kontu, publicējat sludinājumus, iesniedzat pieteikumus, sazināties ar citiem lietotājiem vai citādi mijiedarbojaties ar Platformu.
        </p>
        <p>
            Politika piemērojama saskaņā ar Eiropas Parlamenta un Padomes 2016. gada 27. aprīļa Regulu (ES) 2016/679 par fizisku personu aizsardzību attiecībā uz personas datu apstrādi un šādu datu brīvu apriti un ar ko atceļ Direktīvu 95/46/EK (Vispārīgā datu aizsardzības regula jeb VPDAR jeb GDPR) un Latvijas Republikas <em>Fizisko personu datu apstrādes likumu</em> un citiem piemērojamiem tiesību aktiem.
        </p>

        <h2 id="p1">1. Pārzinis un kontaktinformācija</h2>
        <p>
            Personas datu pārzinis ir persona, kas nosaka personas datu apstrādes nolūkus un līdzekļus. Par Platformas personas datu apstrādes pārzini uzskatāms HomeEstate platformas operators (turpmāk — Pārzinis).
        </p>
        <div class="privacy-box">
            <p><strong>Saziņa par datu aizsardzību:</strong> e-pasts <a href="mailto:<?php echo htmlspecialchars($privacyEmail, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($privacyEmail, ENT_QUOTES, 'UTF-8'); ?></a>, tālrunis <?php echo htmlspecialchars($privacyPhone, ENT_QUOTES, 'UTF-8'); ?>, adrese: <?php echo htmlspecialchars($privacyAddress, ENT_QUOTES, 'UTF-8'); ?>.</p>
            <p>Ja pakalpojumu sniedz juridiska persona (piemēram, sabiedrība ar ierobežotu atbildību), tās firmas nosaukumu, reģistrācijas numuru un juridisko adresi var pieprasīt, rakstot uz norādīto e-pasta adresi.</p>
        </div>

        <h2 id="p2">2. Kādus personas datus mēs apstrādājam</h2>
        <p>Atkarībā no tā, kā izmantojat Platformu, mēs varam apstrādāt šādus datu veidus:</p>
        <ul>
            <li><strong>Konta un identifikācijas dati:</strong> lietotājvārds, e-pasta adrese, parole (glabāta drošā veidā, parasti jaukšanas funkcijas veidā), loma (piemēram, lietotājs, īpašnieks), sesijas dati.</li>
            <li><strong>Profila un kontaktdati:</strong> vārds, uzvārds vai attēlojamais vārds, tālruņa numurs, profila attēls (ja augšupielādējat), cita jūsu brīvprātīgi norādītā informācija profilā.</li>
            <li><strong>Sludinājumu un īpašuma dati:</strong> teksti, cenas, atrašanās vieta, attēli un cita informācija, ko ievadāt, publicējot vai pārvaldot sludinājumus.</li>
            <li><strong>Pieteikumu un darījumu dati:</strong> informācija, ko sniedzat, iesniedzot pieteikumu īrei, īstermiņa īrei vai pirkumam (piemēram, vēlamie datumi, komentāri, piedāvājuma summa), kā arī pieteikumu statuss un saistītie ieraksti Platformā.</li>
            <li><strong>Sarakstes un atbalsta dati:</strong> ziņojumu saturs, metadati (piemēram, nosūtīšanas laiks), pieprasījumi klientu atbalstam.</li>
            <li><strong>Maksājumu un abonementu dati:</strong> informācija, kas nepieciešama maksājumu vai abonementu apstrādei (piemēram, darījuma identifikators, summa, valūta, statuss). Kartes pilnu numuru Pārzinis parasti neapstrādā — to apstrādā sertificēts maksājumu pakalpojumu sniedzējs saskaņā ar savu privātuma politiku.</li>
            <li><strong>Tehniskie un lietošanas dati:</strong> IP adrese, ierīces veids, pārlūkprogrammas veids, piekļuves laiks, kļūdas žurnāli, sīkdatņu identifikatori, apkopotā statistika par vietnes lietošanu (piemēram, apmeklētās lapas), lai nodrošinātu drošību, novērstu ļaunprātīgu izmantošanu un uzlabotu pakalpojumu kvalitāti.</li>
        </ul>

        <h2 id="p3">3. Apstrādes tiesiskais pamats</h2>
        <p>Mēs apstrādājam personas datus, pamatojoties uz vienu vai vairākiem šādiem tiesiskajiem pamatiem (GDPR 6. panta 1. punkts un, attiecīgajos gadījumos, 9. pants):</p>
        <ul>
            <li><strong>Līguma izpilde vai pasākumi pirms līguma noslēgšanas</strong> — lai izveidotu un uzturētu jūsu kontu, nodrošinātu Platformas funkcijas, apstrādātu pieteikumus un saistītos darījumus.</li>
            <li><strong>Juridisks pienākums</strong> — lai izpildītu piemērojamos tiesību aktus (piemēram, grāmatvedības, nodokļu vai informācijas pieprasījumu gadījumā).</li>
            <li><strong>Pārzinīga leģitīmā interese</strong> — lai nodrošinātu IT un tīkla drošību, novērstu krāpšanu, veiktu statistiku un analīzi (apkopotā veidā), uzturētu un uzlabotu Platformu, tiesību aizsardzību un pierādījumu vākšanu, izņemot gadījumus, kad jūsu intereses un tiesības ir svarīgākas.</li>
            <li><strong>Piekrišana</strong> — ja konkrētai apstrādei (piemēram, noteiktiem mārketinga e-pastiem vai neobligātām sīkdatnēm) nepieciešama atsevišķa piekrišana, mēs to pieprasīsim atsevišķi un jūs varēsiet to atsaukt jebkurā laikā bez ietekmes uz līguma izpildi, ja apstrāde nav nepieciešama līgumam.</li>
        </ul>

        <h2 id="p4">4. Apstrādes nolūki</h2>
        <p>Personas datus mēs izmantojam šādiem nolūkiem:</p>
        <ul>
            <li>Platformas reģistrācijas, autentifikācijas un profila pārvaldības nodrošināšanai;</li>
            <li>sludinājumu publicēšanas, meklēšanas un attēlošanas nodrošināšanai;</li>
            <li>pieteikumu saņemšanai, izskatīšanai un statusa pārvaldībai;</li>
            <li>lietotāju savstarpējās saziņas (piemēram, ziņu) nodrošināšanai;</li>
            <li>klientu atbalsta sniegšanai un ar lietotāju saziņai;</li>
            <li>maksājumu un abonementu (ja piemērojams) apstrādei;</li>
            <li>drošības incidentu novēršanai, krāpšanas novēršanai un tiesību aizsardzībai;</li>
            <li>tiesību aktos noteikto pienākumu izpildei;</li>
            <li>ar jūsu piekrišanu — mārketingam un paziņojumiem, ja tādi tiek piedāvāti.</li>
        </ul>

        <h2 id="p5">5. Datu saņēmēji un apstrādātāji</h2>
        <p>
            Pārzinis nepārdod jūsu personas datus. Datus varam izpaust tikai ierobežotā apjomā un tikai tad, ja tam ir tiesisks pamats:
        </p>
        <ul>
            <li><strong>IT un mākoņpakalpojumu sniedzēji</strong> (serveru mitināšana, e-pasta nosūtīšana, dublēšana), kas darbojas kā <em>apstrādātāji</em> saskaņā ar rakstisku līgumu un instrukcijām;</li>
            <li><strong>maksājumu pakalpojumu sniedzēji</strong> maksājumu veikšanai;</li>
            <li><strong>tiesībaizsardzības un valsts iestādes</strong>, ja to pieprasa likums un pieprasījums ir tiesiski pamatots;</li>
            <li><strong>profesionālie konsultanti</strong> (piemēram, juristi, grāmatveži) konfidencialitātes ietvaros.</li>
        </ul>
        <p>
            Ja dati tiek pārsūtīti uz valstīm ārpus Eiropas Ekonomikas zonas, mēs nodrošināsim atbilstošus aizsardzības līdzekļus (piemēram, Eiropas Komisijas lēmumu par atbilstību, standarta līguma klauzulas vai citus VPDAR atļautos līdzekļus), ja to prasa piemērojamie tiesību akti.
        </p>

        <h2 id="p6">6. Glabāšanas termiņš</h2>
        <p>
            Personas datus glabājam tik ilgi, cik nepieciešams attiecīgā nolūka sasniegšanai, ja vien ilgāks periods nav nepieciešams likumā noteikto pienākumu izpildei vai tiesību prasību izcelsmei, izpildei vai aizsardzībai. Pēc termiņa beigām datus dzēšam vai anonimizējam, ja likums neparedz ilgāku glabāšanu.
        </p>
        <p>
            Piemēram, grāmatvedības dokumenti var tikt glabāti saskaņā ar Latvijas Republikas normatīvajiem aktiem par grāmatvedību; sesijas un drošības žurnāli — īsāku, tehniski pamatotu periodu.
        </p>

        <h2 id="p7">7. Jūsu tiesības</h2>
        <p>Jums ir šādas tiesības attiecībā uz jūsu personas datiem:</p>
        <ul>
            <li><strong>Tiesības piekļūt</strong> — pieprasīt informāciju par to, vai mēs apstrādājam jūsu datus, un saņemt kopiju no tiem datiem, ko apstrādājam;</li>
            <li><strong>Tiesības labot</strong> — pieprasīt neprecīzu datu labošanu;</li>
            <li><strong>Tiesības dzēst („tiesības tikt aizmirstam“)</strong> — noteiktos apstākļos pieprasīt datu dzēšanu;</li>
            <li><strong>Tiesības ierobežot apstrādi</strong> — noteiktos apstākļos;</li>
            <li><strong>Tiesības iebilst pret apstrādi</strong>, kas balstās uz leģitīmu interesi, ņemot vērā jūsu konkrēto situāciju;</li>
            <li><strong>Tiesības uz datu pārnesamību</strong> — strukturētā, plaši izmantotā un mašīnlasāmā formātā attiecībā uz datiem, ko sniedzāt un ko apstrādājam, pamatojoties uz piekrišanu vai līgumu, ja tehniski iespējams;</li>
            <li><strong>Tiesības atsaukt piekrišanu</strong>, ja apstrāde balstās uz piekrišanu, neatkarīgi no tā, vai piekrišana tika dota pirms VPDAR piemērošanas sākuma;</li>
            <li><strong>Tiesības iesniegt sūdzību</strong> uzraudzības iestādei.</li>
        </ul>
        <p>
            Lai izmantotu šīs tiesības, sazinieties ar Pārzini, izmantojot augšā norādīto e-pasta adresi. Atbildi sniegsim saprātīgā termiņā un vismaz viena mēneša laikā, ja vien likums neparedz īsāku termiņu. Dažos gadījumos mēs varam pieprasīt jūsu identitātes apliecināšanu, lai aizsargātu citu personu datus.
        </p>
        <h3>Uzraudzības iestāde</h3>
        <p>
            Jums ir tiesības iesniegt sūdzību <strong>Datu valsts inspekcijā</strong> (DVI), ja uzskatāt, ka personas datu apstrāde pārkāpj jūsu tiesības. DVI kontaktinformācija un veidlapas pieejamas vietnē <a href="https://www.dvi.gov.lv/lv/" rel="noopener noreferrer" target="_blank">https://www.dvi.gov.lv/</a>. Jums var būt arī tiesības vērsties tiesā.
        </p>

        <h2 id="p8">8. Automatizēta lēmumu pieņemšana un profilēšana</h2>
        <p>
            Mēs neveicam automatizētu lēmumu pieņemšanu, kas jums rada tiesiskas sekas vai līdzīgi būtiski ietekmē jūsu situāciju, balstoties vienīgi uz automatizētu datu apstrādi, ja vien atsevišķi nepaziņojam citādi. Ja nākotnē tiktu ieviesta būtiska profilēšana, mēs sniegsim nepieciešamo informāciju un, ja prasīs likums, pieprasīsim piekrišanu.
        </p>

        <h2 id="p9">9. Sīkdatnes un līdzīgas tehnoloģijas</h2>
        <p>
            Platforma var izmantot sīkdatnes un līdzīgas tehnoloģijas, lai nodrošinātu sesiju, autentifikāciju, drošību, valodas iestatījumus un (atļautā apjomā) statistiku. Obligātās sīkdatnes parasti balstās uz leģitīmu interesi vai līgumu; analītiskajām un mārketinga sīkdatnēm, ja tās tiek izmantotas, tiks pieprasīta piekrišana atbilstoši <em>Elektronisko komunikāciju likuma</em> prasībām un labas prakses vadlīnijām.
        </p>
        <p>
            Jūs varat pārvaldīt sīkdatnes pārlūkprogrammas iestatījumos. Dažu sīkdatņu bloķēšana var ierobežot Platformas funkcionalitāti.
        </p>

        <h2 id="p10">10. Drošība</h2>
        <p>
            Mēs īstenojam atbilstošus tehniskus un organizatoriskus pasākumus, lai aizsargātu personas datus pret nejaušu vai nelikumīgu iznīcināšanu, pazaudēšanu, grozīšanu, neatļautu izpaušanu vai piekļuvi. Neviena drošības sistēma nav absolūti necaurlaidīga — ja konstatējat iespējamu drošības trūkumu, lūdzu, nekavējoties informējiet mūs.
        </p>

        <h2 id="p11">11. Bērni</h2>
        <p>
            Platforma nav paredzēta personām, kas jaunākas par 16 gadiem (vai citu piemērojamo vecuma robežu saskaņā ar tiesību aktiem). Ja uzzinām, ka esam apkopojuši datus par bērnu bez atbilstošas vecāku atbildības pārstāvja piekrišanas (ja tāda nepieciešama), veiksim pasākumus datu dzēšanai.
        </p>

        <h2 id="p12">12. Politikas izmaiņas</h2>
        <p>
            Mēs varam atjaunināt šo Politiku, lai atspoguļotu izmaiņas pakalpojumos vai tiesību aktos. Atjaunināta versija tiks publicēta šajā lapā ar norādītu atjaunināšanas datumu. Ja izmaiņas ir būtiskas, mēs varam sniegt papildu paziņojumu (piemēram, e-pastā vai Platformas paziņojumā), ja likums to prasa.
        </p>

        <h2 id="p13">13. Kontakti</h2>
        <p>
            Jautājumi par šo Politiku un personas datu apstrādi: <a href="mailto:<?php echo htmlspecialchars($privacyEmail, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($privacyEmail, ENT_QUOTES, 'UTF-8'); ?></a>, tālrunis <?php echo htmlspecialchars($privacyPhone, ENT_QUOTES, 'UTF-8'); ?>.
        </p>
    </article>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
