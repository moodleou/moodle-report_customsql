<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Lang strings for report/customsql
 *
 * @package report_customsql
 * @copyright 2012 Paweł Suwiński <dracono@wp.pl>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['addreport'] = 'Nowy raport';
$string['anyonewhocanveiwthisreport'] = 'Każdy z dostępem do raportów ad-hoc (report/customsql:view)';
$string['archivedversions'] = 'Zarchiwizowane wersje tego raportu';
$string['automaticallymonthly'] = 'Automatycznie pierwszego dnia miesiąca';
$string['automaticallyweekly'] = 'Automatycznie pierwszego dnia tygodnia';
$string['availablereports'] = 'Raporty na żądanie';
$string['availableto'] = 'Dostęp: $a.';
$string['backtoreportlist'] = 'Wróć do listy raportów';
//$string['changetheparameters'] = '';
$string['customsql:definequeries'] = 'Tworzenie raportów ad-hoc';
$string['customsql:view'] = 'Podgląd wyniku/ uruchamianie raportów ad-hoc';
$string['deleteareyousure'] = 'Czy na pewno chcesz usunąć raport?';
$string['deletethisreport'] = 'Usuń raport';
$string['description'] = 'Opis';
$string['displayname'] = 'Nazwa raportu';
$string['displaynamerequired'] = 'Musisz wpisać nazwę raportu';
$string['displaynamex'] = 'Nazwa raportu: $a';
$string['downloadthisreportascsv'] = 'Pobierz wyniki jako CSV';
$string['editingareport'] = 'Edytowanie raportu ad-hoc';
$string['editthisreport'] = 'Edytuj raport';
//$string['enterparameters'] = '';
$string['errordeletingreport'] = 'Błąd podczas usuwania raportu.';
$string['errorinsertingreport'] = 'Błąd podczas dodawania raportu.';
$string['errorupdatingreport'] = 'Błąd podczas aktualizacji raportu.';
$string['invalidreportid'] = 'Błędny id raportu $a.';
$string['lastexecuted'] = 'Ostatnie uruchomienie raportu: $a->lastrun. Czas wykonania: {$a->lastexecutiontime}s.';
$string['manually'] = 'Na żądanie';
$string['manualnote'] = 'Poniższe raporty są uruchamiane na żądanie po kliknięciu na link.';
$string['morethanonerowreturned'] = 'Wynik zawiera więcej niż jeden wiersz. Ten raport powinien zwracać tylko jeden wiersz.';
$string['nodatareturned'] = 'Raport nie zwraca żadnych danych.';
$string['noexplicitprefix'] = 'Użyj prefix_ w zapytaniu SQL, nie $a.';
$string['noreportsavailable'] = 'Brak dostępnych raportów';
$string['norowsreturned'] = 'Wynik nie zawiera żadnego wiersza. Ten raport powinien zwracać jeden wiersz.';
$string['nosemicolon'] = 'Użycie znaku ";" w zapytaniu SQL jest niedozwolone.';
$string['notallowedwords'] = 'Użycie słowa "$a" w zapytaniu SQL jest niedozwolone.';
$string['note'] = 'Uwagi';
$string['notrunyet'] = 'Raport nie był jeszcze uruchamiany.';
$string['onerow'] = 'Raport zwraca jeden wiersz agregując dane';
//$string['parametervalue'] = '';
$string['pluginname'] = 'Raporty ad-hoc';
$string['queryfailed'] = 'Błąd podczas wykonywania zapytania: $a';
$string['querynote'] = '<ul>
<li>Tag <tt>%%%%WWWROOT%%%%</tt> w wynikach będzie zastąpiony przez <tt>$a</tt>.</li>
<li>Każde pole wynikowe wyglądające jak URL będzie automatycznie przetworzone na aktywny link.</li>
<li>Tag <tt>%%%%USERID%%%%</tt> w zapytaniu SQL przed wykonaniem będzie zastąpiony identyfikatorem id użytkownika przeglądającego raport.</li>
<li>W przypadku automatycznych raportów tagi <tt>%%%%STARTTIME%%%%</tt> i <tt>%%%%ENDTIME%%%%</tt> w zapytaniach SQL przed wykonaniem będą zastępowane Uniksowymi znacznikami czasu oznaczającymi początek i koniec raportowanego okresu (tygodnia/ miesiąca).</li>
</ul>';// Note, new last li point needs to be translated.
//$string['queryparameters'] = '';
//$string['queryparams'] = '';
//$string['queryparamschanged'] = '';
$string['queryrundate'] = 'data wykonania raportu';
$string['querysql'] = 'Zapytanie SQL';
$string['querysqlrequried'] = 'Musisz podać zapytanie SQL.';
$string['recordlimitreached'] = 'Raport przekroczył dozwolony limit wierszy równy $a. Część końcowych wierszy została pominięta.';
$string['reportfor'] = 'Raport dla: $a';
$string['runable'] = 'Sposób wykonania';
$string['runablex'] = 'Wykonaj: $a';
$string['schedulednote'] = 'Poniższe raporty są automatyczne uruchamiane pierwszego dnia tygodnia lub miesiąca obejmując wynikami miniony okres (tydzień lub miesiąc). Klikając na link możesz zobaczyć dotychczasowe wyniki.';
$string['scheduledqueries'] = 'Zaplanowane raporty';
$string['typeofresult'] = 'Wynik raportu';
$string['unknowndownloadfile'] = 'Unknown download file.';
$string['userswhocanconfig'] = 'Tylko administratorzy (moodle/site:config)';
$string['userswhocanviewsitereports'] = 'Użytkownicy z dostępem do wszystkich raportów (moodle/site:viewreports)';
$string['whocanaccess'] = 'Dostęp do raportu';
