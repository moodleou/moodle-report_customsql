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
 * Braziallian Portugese Lang strings for report/customsql.
 *
 * Although this is Brazillan Portugese, I am storing it in the pt_utf8 folder
 * until such time as someone produces an Portugese Portugese translation. Tim.
 *
 * @package report_customsql
 * @copyright 2010 Daniel Neis, http://www.ufsc.br/
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['addreport'] = 'Adicionar uma nova consulta';
$string['anyonewhocanveiwthisreport'] = 'Qualquer um que possa ver este relatório (report/courseoverview:view)';
$string['archivedversions'] = 'Versões arquivadas desta consulta';
$string['automaticallymonthly'] = 'Agendada, no primeiro dia de cada mês';
$string['automaticallyweekly'] = 'Agendada, no primeiro dia de cada semana';
$string['availablereports'] = 'Consultas sob demanda';
$string['availableto'] = 'Disponível para $a.';
$string['backtoreportlist'] = 'Voltar para a lista de consultas';
//$string['changetheparameters'] = '';
$string['customsql:definequeries'] = 'Definir consultas personalizadas';
$string['customsql:view'] = 'Ver relatório de consultas personalizadas';
$string['deleteareyousure'] = 'Você tem certeza que quer excluir esta consulta?';
$string['deletethisreport'] = 'Excluir esta consulta';
$string['description'] = 'Descrição';
$string['displayname'] = 'Nome da consulta';
$string['displaynamerequired'] = 'Você deve inserir um nome para a consulta';
$string['displaynamex'] = 'Nome da consulta: $a';
$string['downloadthisreportascsv'] = 'Faça o download dos resultados no formato CSV';
$string['editingareport'] = 'Editando uma consulta ad-hoc';
$string['editthisreport'] = 'Editar esta consulta';
//$string['enterparameters'] = '';
$string['errordeletingreport'] = 'Erro excluindo uma consulta.';
$string['errorinsertingreport'] = 'Erro incluindo uma consulta.';
$string['errorupdatingreport'] = 'Erro atualizando uma consulta.';
$string['invalidreportid'] = 'O id da consulta ($a) é inválido.';
$string['lastexecuted'] = 'Esta consulta foi executada pela última vez em $a->lastrun. Ela levou $a->lastexecutiontime segundos para executar.';
$string['manually'] = 'Sob demanda';
$string['manualnote'] = 'Estas consultas são executadas sob demanda quando você clica no link para ver os resultados.';
$string['morethanonerowreturned'] = 'Mais de uma linha foi retornada. Esta consulta deveria retornar apenas uma linha.';
$string['nodatareturned'] = 'Esta consulta não retornou nenhum dado.';
$string['noexplicitprefix'] = 'Por favor, utilize prefix_ no SQL e não $a.';
$string['noreportsavailable'] = 'Nenhuma consulta disponível';
$string['norowsreturned'] = 'Nenhuma linha foi retornada. Esta consulta deveria retornar uma linha.';
$string['nosemicolon'] = 'Não é permitido utilizar o caractere ponto-e-vírgula (;) no SQL.';
$string['notallowedwords'] = 'Não é permitido utilizar as palavras $a no SQL.';
$string['note'] = 'Anotações';
$string['notrunyet'] = 'Esta consulta ainda não foi executada.';
$string['onerow'] = 'A consulta retorna uma linha, acumule os resultados uma linha por vez';
//$string['parametervalue'] = '';
$string['pluginname'] = 'Consultas Ad-hoc';
$string['queryfailed'] = 'Erro executando a consulta: $a';
$string['querynote'] = '<ul>
<li>O token <tt>%%WWWROOT%%</tt> nos resultados será substituído por <tt>$a</tt>.</li>
<li>Qualquer campo na saída que se parecer com uma URL será automaticamente transformado em um link.</li>
<li>O token <tt>%%USERID%%</tt> na consulta será substituído pelo id do usuário que está visualizando a consulta, antes da consulta ser executada.</li>
<li>Para consultas agendadas, os tokens <tt>%%STARTTIME%%</tt> e <tt>%%ENDTIME%%</tt> são substituídos, antes da consulta ser executada, pelos Unix timestamps do início e do fim do mês/semana a ser reportado.</li>
</ul>';// Note, new last li point needs to be translated.
//$string['queryparameters'] = '';
//$string['queryparams'] = '';
//$string['queryparamschanged'] = '';
$string['queryrundate'] = 'data de execução da consulta';
$string['querysql'] = 'Consulta SQL';
$string['querysqlrequried'] = 'Você deve inserir algum SQL.';
$string['recordlimitreached'] = 'Esta consulta atingiu o limite de $a linhas. Algumas colunas devem ter sido omitidas do finalEsta consulta atingiu o limite de $a linhas. Algumas colunas devem ter sido omitidas do final.';
$string['reportfor'] = 'Consulta executa em $a';
$string['runable'] = 'Executar';
$string['runablex'] = 'Executar: $a';
$string['schedulednote'] = 'Estas consultas são executadas automaticamente no primeiro dia de cada semana ou mês, para relatar o último mês ou semana. Estes links permitem que você veja os resultados já acumulados.';
$string['scheduledqueries'] = 'Consultas agendadas';
$string['typeofresult'] = 'Tipo do resultado';
$string['unknowndownloadfile'] = 'Arquivo de download desconhecido.';
$string['userswhocanconfig'] = 'Apenas administradores (moodle/site:config)';
$string['userswhocanviewsitereports'] = 'Usuários que podem ver relatórios de sistema (moodle/site:viewreports)';
$string['whocanaccess'] = 'Quem pode acessar esta consulta';
