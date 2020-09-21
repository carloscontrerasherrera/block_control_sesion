<?php
	global $tipo_filtro,$grupo_filtro;
	function lista_usuarios($instancia,$usuario,$ampliado=false,$tipo_tabla=0,$fecha="now",$num_dias=1,$mes=1,$year=1,$curso=1,$grupo=0,$fecha_fin="now")
	{
		global $COURSE, $DB, $PAGE,$USER,$DIF_MY_UTC,$config;
		$a_fecha=get_string('abrev_fecha', 'block_control_sesion');
		$filtro_us="";
		$filas=array();
		$dias_valor=0;
		$config=obtener_config($instancia);
		$ini=7;
		$interval=30;
		if(isset($config->ini))
			$ini=$config->ini;
		if(isset($config->interval))
			$interval=$config->interval;
		if ($usuario!=0)
			$filtro_us=" AND r.userid=".$usuario;
		if ($tipo_tabla==0)
		{
			if ($grupo==0)
				$sql_us='SELECT u.id, u.firstname, u.lastname,MAX(m.timecreated)ultimo FROM {user} u LEFT JOIN {logstore_standard_log} m ON m.userid=u.id WHERE u.id in (SELECT r.userid FROM {role_assignments} r, {context} o where o.id=r.contextid and o.instanceid='.$COURSE->id.$filtro_us.') GROUP BY u.id';
			else
				$sql_us='SELECT u.id, u.firstname, u.lastname,MAX(m.timecreated)ultimo FROM {user} u LEFT JOIN {logstore_standard_log} m ON m.userid=u.id WHERE u.id in (SELECT r.userid FROM {groups_members} r WHERE r.groupid='.$grupo.') GROUP BY u.id';
		}
		else
		{
			if ($grupo==0)
				$sql_us='SELECT id, firstname, lastname FROM {user} WHERE id in (SELECT r.userid FROM {role_assignments} r, {context} o where o.id=r.contextid and o.instanceid='.$COURSE->id.$filtro_us.')';
			else
				$sql_us='SELECT id, firstname, lastname FROM {user} WHERE id in (SELECT r.userid FROM {groups_members} r WHERE r.groupid='.$grupo.')';
		}
		//print $sql_us;
		if ($usuarios=$DB->get_records_sql($sql_us))
		{
			
			$hoy=obtener_inicio($fecha,$ini);
			$manana=obtener_final($hoy,$num_dias);
			//print $num_dias." ".$fecha_fin." ".$manana->format($a_fecha.' H:i')."  ";
			if (($fecha_fin!="now" || $fecha_fin=='') && $num_dias==1)
				$manana=obtener_final(obtener_inicio($fecha_fin,$ini),1);
			//print $manana->format($a_fecha.' H:i');
			
			// Cabeceras de cada tipo de tabla
			if ($tipo_tabla<=1)
			{
				$dias_valor=($manana->diff($hoy))->d;
				$campos=array();
				array_push($campos,get_string('registros_entre', 'block_control_sesion').'<br>'.$hoy->format($a_fecha.' H:i').'&nbsp;&nbsp;'.get_string('and', 'block_control_sesion').'&nbsp;&nbsp;'.$manana->format($a_fecha.' H:i')."<br><br>");
				array_push($filas,$campos);
			}
			$campos=array();
			if ($tipo_tabla==0)
			{
				array_push($campos,"<th>".get_string('alumno', 'block_control_sesion'));
				array_push($campos,get_string('ultimo_inicio', 'block_control_sesion'));
				array_push($campos,get_string('inicio', 'block_control_sesion'));
				array_push($campos,get_string('final', 'block_control_sesion'));
				array_push($campos,get_string('interacciones', 'block_control_sesion'));
				array_push($campos,get_string('tiempo_estimado', 'block_control_sesion'));
				array_push($campos,"");
			}
			if ($tipo_tabla==1)
			{
				//se pone cabecera para cada usuario
				
			}
			if ($tipo_tabla==2)
			{
				$fin=new DateTime($fecha_fin);
				$h=new DateTime($fecha);
				$campos=array();
				array_push($campos,get_string('registros_entre', 'block_control_sesion').'<br>'.$hoy->format($a_fecha.' H:i').'&nbsp;&nbsp;'.get_string('and', 'block_control_sesion').'&nbsp;&nbsp;'.$manana->format($a_fecha.' H:i')."<br><br>");
				array_push($filas,$campos);
				$campos=array();
				array_push($campos,"<th>".get_string('alumno', 'block_control_sesion'));
				while ($h<=$fin)
				{
					array_push($campos,$h->format('d-m'));
					$h=(new DateTime($h->format('Y-m-d').' + 1 days'));
				}
				array_push($campos,"Total");
				
			}
			if ($tipo_tabla==3)
			{
					array_push($campos,nombre_mes($mes).' - '.$year);
					array_push($filas, $campos);
					$campos=array();
					array_push($campos,"<th>".get_string('alumno', 'block_control_sesion'));
					$h=new DateTime($year.'-'.$mes.'-01');
					$m=$h->format("m");
					for ($s=1;$s<=5;$s++)
					{
						$fin=new DateTime($h->format('Y-m-d H:i:s')."+ 6 days");
						if ($m!=$fin->format("m"))
							$fin=new DateTime($h->format("Y-m-").$h->format("t").$h->format(" H:i:s"));
						array_push($campos,$h->format('d')." → ".$fin->format('d'));
						$h=new DateTime($h->format('Y-m-d H:i:s')."+ 7 days");
					}
					array_push($campos,get_string('total_mes', 'block_control_sesion'));
					array_push($campos,"");
			}else
			if ($tipo_tabla==4)
			{
				//print_object($curso);
				array_push($campos,get_string('curso', 'block_control_sesion').' '.$curso.'-'.($curso+1));
				array_push($filas, $campos);
				$campos=array();
				array_push($campos,"<th>".get_string('alumno', 'block_control_sesion'));
				for($x=1;$x<=12;$x++)
				{	
					//sustituir year por curso
					$res=ajuste_mes_curso($x,$curso);
					$url_detalle_mes = new moodle_url('/blocks/control_sesion/view.php' , array('id'=>$instancia,'c' => $COURSE->id,'t' => 2,'u' => 0,"f"=>$res["year"].'-'.$res["mes"].'-01',"ff"=>$res["year"].'-'.$res["mes"].'-'.dias_mes($res["mes"],$res["year"]),"g"=>$grupo));
					$texto='<a href="'.$url_detalle_mes.'">'.substr(nombre_mes($res["mes"]),0,3).'</a>';
					array_push($campos,$texto);
				}
				array_push($campos,"TOT");
			}
			if ($tipo_tabla==5)
			{
				array_push($campos,nombre_mes($mes).' - '.$year);
				array_push($filas, $campos);
				$campos=array();
				array_push($campos,"<th>");
				for ($x=1;$x<=7;$x++)
					array_push($campos,nombre_dia($x));
			}
			
			array_push($filas, $campos);
			foreach ($usuarios as $u) {//crea un elemento de la lista para cada página
				
				if ($usuario!=0 && $usuario!=$u->id)
					continue;
				$campos=array();
				//$sql='FROM {logstore_standard_log} m where userid='.$u->id.' AND courseid='.$COURSE->id.' AND '.campo_fecha().'>="'.$hoy->format('Y-m-d H:i:s').'" AND '.campo_fecha().'<="'.$manana->format('Y-m-d H:i:s').'"';
				$sql='FROM {logstore_standard_log} m where userid='.$u->id.' AND courseid='.$COURSE->id.' AND (m.timecreated)>="'.fecha_servidor($hoy->format('Y-m-d H:i:s')).'" AND (m.timecreated)<="'.fecha_servidor($manana->format('Y-m-d H:i:s')).'"';
				//print $sql;
				$hora_ini='';
				$intervalo='';
				if ($hini=$DB->get_record_sql('SELECT MIN(timecreated) timecreated '.$sql))
				{
					if (!empty($hini->timecreated))
					{
						$hora_ini=date("H:i:s",$hini->timecreated);
						if ($hfin=$DB->get_record_sql('SELECT MAX(timecreated) timecreated '.$sql))
						{	
							$hora_fin=date("H:i:s",$hfin->timecreated);
							$intervalo=' ['.$hora_ini." → ".$hora_fin.']'."<br>";
						}
					}
				}
/* 				if ($hini=$DB->get_record_sql($sql.' LIMIT 1'))
					$hora_ini=date("H:i:s",$hini->timecreated);
				$hora_fin='';
				$intervalo='';
				if ($hfin=$DB->get_record_sql($sql.' ORDER BY m.timecreated DESC LIMIT 1'))
					$hora_fin=date("H:i:s",$hfin->timecreated);
 */				
//return $filas;
				// Datos de cada usuario. Contenido de las tablas
				
				//color de la celda resaltada según límites
				if ($tipo_tabla==0)
				{
					$res=tiempo_usuario_dia($u->id,$hoy->format('Y-m-d'),$ini,$interval,1,$manana->format('Y-m-d'));
					$tiempo="&nbsp;";
					if ($res["total"]!=0)
						$tiempo=tiempo_segundos($res["total"]);
					$url_detalle = new moodle_url('/blocks/control_sesion/view.php' , array('c'=>$COURSE->id,'id'=>$instancia,'t' => 1,'a' => true, "f"=>$fecha, "ff"=>$fecha,"u"=>$u->id,"g"=>$grupo));
					array_push($campos,$u->firstname." ".$u->lastname);
					array_push($campos,date(get_string('abrev_fecha', 'block_control_sesion')." H:i:s",$u->ultimo));
					//array_push($campos,ultimo_inicio_usuario($u->id));
					array_push($campos,$res["inicio"]);
					array_push($campos,$res["final"]);
					
					$color=color_celda($res["interacciones"],$dias_valor);
					array_push($campos,$color.intval($res["interacciones"])." ".get_string('abrev_int', 'block_control_sesion'));
					if ($res["total"]!=0)
					{
						array_push($campos,$tiempo);
						array_push($campos,'<a href="'.$url_detalle.'">'.get_string('detalles', 'block_control_sesion').'</a>');
					}
					else
					{
						array_push($campos,get_string('sin_tiempo_dia', 'block_control_sesion'));
					}
					//array_push($campos,(new DateTime("now"))->format("H:i:s"));
					
				}
				if ($tipo_tabla==1)
				{
					//$paso=tiempo_usuario_dia($u->id,$hoy->format('Y-m-d'),$ini,$interval,$num_dias,1,'');
					//array_push($campos,"Detalle de movimientos");
					$res=tiempo_usuario_dia($u->id,$hoy->format('Y-m-d'),$ini,$interval,1,$manana->format('Y-m-d'));
					$tiempo="&nbsp;";
					if ($res["total"]!=0)
						$tiempo=tiempo_segundos($res["total"]);
					if ($res["total"]!=0)
						$t= '<font color="red">'.$intervalo.$tiempo.' '.get_string('estimados', 'block_control_sesion').'</font><br>';
					else
						$t= '<font color="red">'.get_string('sin_tiempo_dia', 'block_control_sesion').'</font>	<br>';
					if ($ampliado)
					{
						//print ($res["pasos"]);
						$campos=array("<azul>".html_writer::tag('div', '<b>'.$u->firstname." ".$u->lastname),
									intval($res["interacciones"])." ".get_string('interacciones', 'block_control_sesion'),$t);
						array_push($filas, $campos);
						
						$campos=array();		
						array_push($campos,"<th>".get_string('inicio', 'block_control_sesion'));
						array_push($campos,get_string('final', 'block_control_sesion'));
						//array_push($campos,"Interacciones");
						array_push($campos,get_string('tiempo_estimado', 'block_control_sesion'));
						array_push($filas, $campos);
						foreach ($res["detalles"] as $d)
							array_push($filas, array($d[0],$d[1],$d[2]));
						$campos=array();		
					}
					else
					{
						$info = html_writer::tag('div', '<b>'.$u->firstname." ".$u->lastname.'</b>');
						$info .= intval($res["interacciones"])." ".get_string('interac_usuario', 'block_control_sesion').'<br>';
						$info .=$t;						
						array_push($campos,$info);
					}
				}
				if ($tipo_tabla==2)
				{
					$t=0;
					array_push($campos,'<b>'.$u->firstname." ".$u->lastname[0].'.</b>');
					$fin=new DateTime($fecha_fin);
					$h=new DateTime($fecha);
					while ($h<=$fin)
					{
						$res=tiempo_usuario_dia($u->id,$h->format('Y-m-d'),$ini,$interval,1,'');
						//array_push($campos,$h->format('d-m'));
						$t=$t+$res["total"];
						$tiempo="&nbsp;";
						if ($res["total"]!=0)
						{
							$tiempo=intval($res['interacciones']).' '.get_string('abrev_int', 'block_control_sesion').'<br>'.tiempo_segundos($res["total"]);
							$url_detalle = new moodle_url('/blocks/control_sesion/view.php' , array('id'=>$instancia,'c' => $COURSE->id,'t' => 1,'a' => true, "f"=>$h->format('Y-m-d'),"ff"=>$h->format('Y-m-d'),"u"=>$u->id,"g"=>$grupo));
							$tiempo='<a href="'.$url_detalle.'">'.$tiempo.'</a>';
						}
						$color=color_celda($res["interacciones"],1);
						array_push($campos,$color.$tiempo);
						$h=(new DateTime($h->format('Y-m-d').' + 1 days'));
					}
					array_push($campos,'<b>'.tiempo_segundos($t).'</b>');
				}
				if ($tipo_tabla==3)
				{
					$t=0;
					array_push($campos,'<b>'.$u->firstname." ".$u->lastname.'.</b>');
					$url_detalle_mes = new moodle_url('/blocks/control_sesion/view.php' , array('id'=>$instancia,'c' => $COURSE->id,'t' => 5,'a' => true, "m"=>$mes,"y"=>$year,"u"=> $u->id,"g"=>$grupo));
					$hoy=new DateTime($year.'-'.$mes.'-01');
					for ($s=1;$s<=5;$s++)
					{
						$dia_fin=date("t",strtotime($hoy->format('Y-m-d')));
						$dias=7;
						$fin=new DateTime($hoy->format('Y-m-d H:i:s')."+ 6 days");
						if ($hoy->format("m")!=$fin->format("m"))
							$dias=$dia_fin-$hoy->format("d")+1;
						$res=tiempo_usuario_dia($u->id,$hoy->format('Y-m-d'),$ini,$interval,$dias,'');
						$t=$t+$res["total"];
						$tiempo="&nbsp;";
						if ($res["total"]!=0)
							$tiempo=intval($res['interacciones']).' int.<br>'.tiempo_segundos($res["total"]);
						$color=color_celda($res["interacciones"],7);
						array_push($campos,$color.$tiempo);
						$hoy=new DateTime($hoy->format('Y-m-d H:i:s')."+ 7 days");
					}
					array_push($campos,'<sincolor><b>'.tiempo_segundos($t).'</b>');
					array_push($campos,'<sincolor><a href="'.$url_detalle_mes.'">'.get_string('detalles', 'block_control_sesion').'</a>');
				}
				if ($tipo_tabla==4)
				{
					$t=0;
					array_push($campos,'<b>'.$u->firstname." ".$u->lastname[0].'.</b>');
					for ($a=1;$a<=12;$a++)
					{
						$ajuste=ajuste_mes_curso($a,$curso);
						$hoy=new DateTime($ajuste["year"].'-'.$ajuste["mes"].'-01');
						$dias=date ( "t",strtotime($hoy->format('Y-m-d')));
						$res=tiempo_usuario_dia($u->id,$hoy->format('Y-m-d'),$ini,$interval,$dias,'');
						$t=$t+$res["total"];
						$tiempo="&nbsp;";
						if ($res["total"]!=0)
						{
							$url_detalle_mes = new moodle_url('/blocks/control_sesion/view.php' , array('id'=>$instancia,'c' => $COURSE->id,'t' => 5,'a' => true, 'u' => $u->id,"m"=>$ajuste["mes"],"y"=>$ajuste["year"],"g"=>$grupo));
							$tiempo='<a href="'.$url_detalle_mes.'">'.intval($res['interacciones']).' int.<br>'.tiempo_segundos($res["total"]).'</a>';
						}
						$color=color_celda($res["interacciones"],$dias);
						array_push($campos,$color.$tiempo);
					
					}
					array_push($campos,'<b>'.tiempo_segundos($t).'</b>');
				}
				if ($tipo_tabla==5)
				{
					$t=0;
					$hoy=new DateTime($year.'-'.$mes.'-01');
					//$hoy=new DateTime('2020-07-01');
					$dias=date ("t",strtotime($hoy->format('Y-m-d')));
					array_push($campos,'<b>'.$u->firstname." ".$u->lastname[0].'.</b>');
					$hoy=new DateTime($hoy->format('Y-m').'-01');
					$ds=$hoy->format('w');
					if ($ds==0)
						$ds=7;
					for ($d=1;$d<=$ds-1;$d++)
					{
						array_push($campos,"-");
					}
					for ($d=1;$d<=$dias;$d++)
					{
						$f=new DateTime($hoy->format('Y-m-'.$d));
						$res=tiempo_usuario_dia($u->id,$f->format('Y-m-d'),$ini,$interval,1,'');
						$t=$t+$res["total"];
						$tiempo="<br>&nbsp;";
						if ($res["total"]!=0)
						{
							$tiempo=tiempo_segundos($res["total"]);
							$url_detalle = new moodle_url('/blocks/control_sesion/view.php' , array('id'=>$instancia,'c' => $COURSE->id,'t' => 1,'a' => true, "f"=>$f->format('Y-m-d'),"u"=>$u->id,"ff"=>$f->format('Y-m-d'),"g"=>$grupo));
							$tiempo='<a href="'.$url_detalle.'">'.intval($res['interacciones']).' '.get_string('abrev_int', 'block_control_sesion').'<br>'.$tiempo.'</a>';
						}
						$color=color_celda($res["interacciones"],1);
						if ($color=="<sincolor>")
						{
							if ($f->format('Y-m-d')==(new DateTime("now"))->format('Y-m-d'))
								$color="<azul>";
						}
						if ($ds==6 || $ds==7)
							$color="<gris>";
						array_push($campos,$color."<b>$d</b><br><center>".$tiempo."</center>");
						$ds++;
						if ($ds==8)
						{
							array_push($filas, $campos);
							$campos=array("");
							$ds=1;
						}
					}
					array_push($filas, $campos);
					$campos=array(get_string('total', 'block_control_sesion'));
							
					array_push($campos,'<b>'.tiempo_segundos($t).'</b>');
				}
				array_push($filas, $campos);
				
			
			}
			$res["datos"]=$filas;
			$res["dias_valor"]=$dias_valor;
			return $res;
		}			
	}
	function obtener_inicio($fecha,$ini)
	{
		if ($ini=="")
			$ini="7";
		$hora_actual=(new DateTime("now"))->format("H:i:s");
		if ($fecha=="now")
			$hora_actual="12:00:00";
		$dia=new DateTime($fecha." ".$hora_actual);
		$limite=new DateTime($dia->format('Y-m-d').' '.$ini.':00:00');
		$hoy = $limite;
		//print_object($hoy);
		$manana=new DateTime($limite->format('Y-m-d H:i:s')."+ 1 days");
		if ($dia<$limite && $dia==$hoy)
		{
			$hoy=new DateTime($hoy->format('Y-m-d H:i:s')."- 1 days");
			$manana=new DateTime($manana->format('Y-m-d H:i:s')."- 1 days");
		}
		return $hoy;	
	}
	function obtener_final($fecha_ini,$num_dias)
	{
		$final=new DateTime($fecha_ini->format('Y-m-d H:i:s')."+ ".$num_dias." days");
		return $final;	
	}
	function tiempo_segundos($segundos)
	{
		$minutos=round($segundos/60);
		$horas=intdiv($minutos,60);
		$minutos=$minutos % 60;		
		return $horas." h. ".$minutos." min.";		
	}
	function ultimo_inicio_usuario($usuario)
	{
		global $COURSE,$DB;
		$a_fecha=get_string('abrev_fecha', 'block_control_sesion');
		//$sql='SELECT id,timecreated ultimo FROM {logstore_standard_log} m where userid='.$usuario.' AND courseid='.$COURSE->id.' ORDER BY id DESC LIMIT 1';
		$sql='SELECT MAX(timecreated) ultimo FROM {logstore_standard_log} m where userid='.$usuario.' AND courseid='.$COURSE->id;
		//print_object($sql);
		if ($evento=$DB->get_record_sql($sql))
		{
			$ultimo=new DateTime();
			$ultimo->setTimestamp($evento->ultimo);
			return $ultimo->format($a_fecha.' H:i:s');
		}
		return "";
	}
	function tiempo_usuario_dia($usuario,$fecha,$ini,$interval,$num_dias,$fecha_fin='')
	{
		global $COURSE,$DB;
		$a_fecha=get_string('abrev_fecha', 'block_control_sesion');
		if ($ini=="")
			$ini="7";
		if (strlen($ini)==1)
			$ini='0'.$ini;
		if ($interval=="")
			$interval="30";
		$interacciones=0;
		$hoy=new DateTime($fecha.' '.$ini.':00:00', core_date::get_user_timezone_object());
		if ($fecha_fin=='')
			$manana=obtener_final($hoy,$num_dias);
		else
		{
			$manana=new DateTime($fecha_fin.' '.$ini.':00:00', core_date::get_user_timezone_object());
			//$manana=obtener_final($fin,1);
		}
		//$sql='SELECT * FROM {logstore_standard_log} m where userid='.$usuario.' AND courseid='.$COURSE->id.' AND '.campo_fecha().'>="'.$hoy->format('Y-m-d H:i:s').'" AND '.campo_fecha().'<="'.$manana->format('Y-m-d H:i:s').'"';
		$sql='SELECT id,timecreated FROM {logstore_standard_log} m where courseid='.$COURSE->id.' AND userid='.$usuario.' AND from_unixtime(m.timecreated)>="'.fecha_servidor($hoy->format('Y-m-d H:i:s')).'" AND from_unixtime(m.timecreated)<="'.fecha_servidor($manana->format('Y-m-d H:i:s')).'"';
		//print_object($sql);
		$total=0;
		$total_int=0;
		$paso="";
		$resultado["inicio"]='';
		$resultado["final"]='';
		$detalles=array();
		//date_default_timezone_set('europe/madrid');
		if ($eventos=$DB->get_records_sql($sql))
		{
			$ant=new DateTime();
			$ant->setTimestamp(0);
			$inicio=0;
			$ant_segundos=0;
			$primero="";
			$interacciones=count($eventos);
			$detalles=array();
			foreach ($eventos as $ev)
			{
				$actual=new DateTime();
				$actual->setTimestamp($ev->timecreated);
				//print_object(usergetdate($ev->timecreated));
				//print(userdate(usergetdate ($actual), '%d/%m/%Y %H:%M:%S').' + ');
				//print( get_string('strftimedatetime', 'core_langconfig').' - ');
				if ($inicio==0)
				{
					$inicio=1;
					$primero=$actual->format($a_fecha.' H:i:s');
					$resultado["inicio"]=$primero;
				}
				else
				{
					$diff = $actual->diff($ant);
					$segundos=($diff->h * 3600 ) + ( $diff->i * 60 ) + $diff->s;
					//print("**".$actual->format($a_fecha.' H:i:s')."**($segundos)<br>");
					if ($segundos<=($interval *60))
					{
						if ($primero=="")
						{
							$primero=$ant->format($a_fecha.' H:i:s');
						}
						$ant_segundos+=$segundos;
					}
					else
					{
						//print("[".($interval * 60)."]");
						if ($ant_segundos>0)
						{
							$paso=$paso.$primero." → ".$ant->format($a_fecha.' H:i:s');
							$total=$total+$ant_segundos;
							$paso=$paso." → ".round($ant_segundos/60)." ".get_string('minutos', 'block_control_sesion')."<br>";
							$linea_det=array($primero,$ant->format($a_fecha.' H:i:s'),round($ant_segundos/60)." ".get_string('minutos', 'block_control_sesion'));
							array_push($detalles,$linea_det);
						}
						if ($primero=="")
						{
							$paso=$paso.$ant->format($a_fecha.' H:i:s')."<br>";
							$linea_det=array($ant->format($a_fecha.' H:i:s'),'','');
							array_push($detalles,$linea_det);
						}											
						$primero="";
						$ant_segundos=0;
					}
					
				}
				$ultimo=$ant;
				$ant=$actual;
			}
			$resultado["final"]=$actual->format($a_fecha.' H:i:s');

			if ($ant_segundos>0)
			{
				if($primero!="")
					$ultimo=$ant;
				$total=$total+$ant_segundos;
				$paso=$paso.$primero." → ".$ultimo->format($a_fecha.' H:i:s');
				$paso=$paso." → ".round($ant_segundos/60)." ".get_string('minutos', 'block_control_sesion')."<br>";
				$linea_det=array($primero,$ultimo->format($a_fecha.' H:i:s'),round($ant_segundos/60)." ".get_string('minutos', 'block_control_sesion'));
				array_push($detalles,$linea_det);
			}
			else
			{
				$paso=$paso.$actual->format($a_fecha.' H:i:s');
				$linea_det=array($actual->format($a_fecha.' H:i:s'),'','');
				array_push($detalles,$linea_det);
			}
		}
		$resultado["detalles"]=$detalles;
		$resultado["interacciones"]=$interacciones;
		$resultado["total"]=$total;
		$resultado["pasos"]=$paso;
		return $resultado;
	}
	function texto_tabla_datos($valores)
	{
		$datos=$valores["datos"];
		//print_object($datos);
		$dias_valor=$valores["dias_valor"];
		$resultado="";
		$tipo="th";
		$arg=array();
		$resultado .= html_writer::start_tag('table',array("class"=>"table"));
		$num=0;
		foreach ($datos as $fila)
		{
			$span=array();
			if (count($fila)==1)
				$span=array('colspan'=>8, 'bgcolor'=>'#eee');
			$resultado .= html_writer::start_tag('tr');
			foreach($fila as $campo)
			{
				if (substr($campo,0,4)=='<th>')
				{
					$campo=substr($campo,4);
					$tipo='th';
					$span["bgcolor"]='#bbb';
				}
				if (substr($campo,0,6)=='<azul>')
				{
					$campo=substr($campo,6);
					$tipo='th';
					$span["bgcolor"]='#6cc';
				}
				if (substr($campo,0,6)=='<gris>')
				{
					$campo=substr($campo,6);
					$tipo='th';
					$span["bgcolor"]='#eee';
				}
				if (substr($campo,0,5)=='<red>')
				{
					$campo=substr($campo,5);
					$tipo='td';
					$span["bgcolor"]='#FF7A7A';
				}
				if (substr($campo,0,8)=='<orange>')
				{
					$campo=substr($campo,8);
					$tipo='td';
					$span["bgcolor"]='#FFBD8C';
				}
				if (substr($campo,0,8)=='<yellow>')
				{
					$campo=substr($campo,8);
					$tipo='td';
					$span["bgcolor"]='#FCFF8C';
				}
				if (substr($campo,0,10)=='<sincolor>')
				{
					$campo=substr($campo,10);
					$tipo='td';
					$span["bgcolor"]='';
				}
				$resultado .= html_writer::start_tag($tipo,$span);
				$resultado .= $campo;
				$resultado .= html_writer::end_tag($tipo);
			}
			if ($num>0)
				$tipo='td';
			$resultado .= html_writer::end_tag('tr');
			$num++;
		}
		$resultado .= html_writer::end_tag('table');
		return($resultado);
	}	
	function descargar_tabla_datos($valores)
	{
		$datos=$valores["datos"];
		$downloadfilename = clean_filename("sesiones.xlsx");
        // Creating a workbook
        $workbook = new MoodleExcelWorkbook("-");
        // Sending HTTP headers
        $workbook->send($downloadfilename);
        // Adding the worksheet
        $myxls = $workbook->add_worksheet();
		/// Print cellls
		$y=0;
		foreach ($datos as $fila)
		{
			$x=0;
			foreach($fila as $campo)
			{
				$myxls->write_string($y,$x,str_replace('&nbsp;',' ',strip_tags($campo)));
				$x++;
			}
			$y++;
		}
        $workbook->close();
	}
	function nombre_mes($mes)
	{
		switch ($mes)
		{
			case 1:return get_string('enero', 'block_control_sesion');
			case 2:return get_string('febrero', 'block_control_sesion');
			case 3:return get_string('marzo', 'block_control_sesion');
			case 4:return get_string('abril', 'block_control_sesion');
			case 5:return get_string('mayo', 'block_control_sesion');
			case 6:return get_string('junio', 'block_control_sesion');
			case 7:return get_string('julio', 'block_control_sesion');
			case 8:return get_string('agosto', 'block_control_sesion');
			case 9:return get_string('septiembre', 'block_control_sesion');
			case 10:return get_string('octubre', 'block_control_sesion');
			case 11:return get_string('noviembre', 'block_control_sesion');
			default: return get_string('diciembre', 'block_control_sesion');
		}
	}
	function nombre_dia($dia)
	{
		switch ($dia)
		{
			case 1:return get_string('lunes', 'block_control_sesion');
			case 2:return get_string('martes', 'block_control_sesion');
			case 3:return get_string('miercoles', 'block_control_sesion');
			case 4:return get_string('jueves', 'block_control_sesion');
			case 5:return get_string('viernes', 'block_control_sesion');
			case 6:return get_string('sabado', 'block_control_sesion');
			default: return get_string('domingo', 'block_control_sesion');
		}
	}
	function dias_mes($mes,$year)
	{
		$f=new DateTime($year.'-'.$mes.'-01');
		return $f->format('t');
	}
	function campo_fecha()
	{
		//genera el texto para poner en el campo fecha para el ajuste horario ya que MySQL guarda en UTC y la consulta devuelve en función del pais del servidor
		//print("UTC".(-($DIF_MY_UTC-2)));
		return 'DATE_ADD(from_unixtime(m.timecreated),INTERVAL '.horas_desfase().' HOUR)';
	}
	function horas_desfase()
	{	
		global $DB,$DIF_MY_UTC;		
		$DIF_MY_UTC=0;
		$Total_Desfase=0;
		if ($res=$DB->get_record_sql('SELECT TIMESTAMPDIFF(HOUR,UTC_TIMESTAMP(),LOCALTIMESTAMP()) dif,UTC_TIMESTAMP() hutc'))
		{
			//desfase del servidor
			$DIF_MY_UTC=$res->dif;
			//desfase del pais del usuario
			$ahora=new DateTime("now");
			$utc=new DateTime($res->hutc);
			$diff = $ahora->diff($utc);
			$Total_Desfase=-($DIF_MY_UTC-$diff->h);
			//print('Diferencia:'.$diff->h);					
		}
		return $Total_Desfase;
	}
	function fecha_servidor($fecha_us)
	{
		$fu=new DateTime($fecha_us);
		$fs=new DateTime($fecha_us.'-'.horas_desfase().'HOUR');
		return $fs->format('Y-m-d H:i:s');
	}
	function obtener_config($instancia)
	{
		//print_object($instancia);
		global $COURSE,$DB;
		$blockrecord = $DB->get_record('block_instances', array('blockname' => 'control_sesion','id' => $instancia), '*', MUST_EXIST);
		$blockinstance = block_instance('control_sesion', $blockrecord);
		return $blockinstance->config;
	}
	function color_celda($valor,$dias_valor)
	{
		global $config;
		//$l_red=
		if (!$config->mostrarcol)
			return "<sincolor>";
		$v=intdiv($valor,$dias_valor);
		if ($v<=$config->red)
			return "<red>";
		else if ($v<=$config->orange)
			return "<orange>";
		else if ($v<=$config->yellow)
			return "<yellow>";
		else
			return "<sincolor>";
	}
	function ajuste_year_curso($fecha)
	{
		global $config;
		$res=$fecha->format('Y');
		if ($fecha->format('m')<$config->mes_ini)
			$res--;
		return $res;
	}
	function ajuste_mes_curso($n,$curso)
	{
		global $config;
		//print_object($n.$curso.$config->mes_ini);
		$res["mes"]=$config->mes_ini+$n-1;
		$res["year"]=$curso;
		if ($res["mes"]>12)
		{
			$res["mes"]=$res["mes"]-12;
			$res["year"]++;
		}
		return $res;
	}
?>