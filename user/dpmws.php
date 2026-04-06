<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Daily Preventive Maintenance Work Sheet</title>
<style>
    body {
        font-family: Arial, sans-serif;
        font-size: 12px;
    }
    table {
        border-collapse: collapse;
        width: 100%;
    }
    th, td {
        border: 1px solid black;
        padding: 4px;
        vertical-align: top;
    }
    .center {
        text-align: center;
    }
    input[type="text"], input[type="date"] {
        width: 100%;
        border: none;
        outline: none;
    }
</style>
</head>
<body>

<form>
<table>
    <tr>
        <th colspan="8" class="center">DAILY PREVENTIVE MAINTENANCE WORK SHEET</th>
    </tr>
    <tr>
        <td>Date:</td>
        <td><input type="date" name="date"></td>
        <td>Regional Depot:</td>
        <td><input type="text" name="regional_depot"></td>
        <td>Group Pool:</td>
        <td><input type="text" name="group_pool"></td>
        <td>Property No.:</td>
        <td><input type="text" name="property_no"></td>
    </tr>
    <tr>
        <td>Equipment:</td>
        <td colspan="7"><input type="text" name="equipment"></td>
    </tr>
    <tr>
        <td colspan="8" style="padding:0; border:none;">
            <table style="width:100%; border-collapse:collapse;">
                <tr>
                    <th rowspan="2" style="width:16%">EQUIPMENT USAGE:</th>
                    <th colspan="2" style="width:16%">ACTUAL</th>
                    <th colspan="2" style="width:16%">PER METER READING</th>
                    <td style="width:13%; text-align:left; border-right:1px solid #000;" rowspan="3">
                        <input type="checkbox" name="om_eqpt"> O & M Eqpt.<br><br>
                        <div style="font-weight:bold; margin-bottom:4px; text-align:center;">Equipment used for</div>
                        <div style="display:flex; flex-direction:column; align-items:flex-start; margin-left:18px;">
                            <label style="margin-bottom:2px;"><input type="checkbox" name="used_construction"> Construction</label>
                            <label><input type="checkbox" name="used_om"> O & M</label>
                        </div>
                    </td>
                    <td style="width:13%; text-align:left;" rowspan="3">
                        <input type="checkbox" name="construction_eqpt"> Construction Eqpt.<br><br>
                        <div style="font-weight:bold; margin-bottom:8px; text-align:center;">Project Fund No.</div>
                        <input type="text" name="project_fund_no" style="width:90%; border-bottom:1px solid #333; border-radius:0;">
                    </td>
                </tr>
                <tr>
                    <th style="width:8%">KMS</th>
                    <th style="width:8%">HRS</th>
                    <th style="width:8%">KMS</th>
                    <th style="width:8%">HRS</th>
                </tr>
                <tr>
                    <td>Today's Accumulated:</td>
                    <td><input type="text" name="actual_kms_today"></td>
                    <td><input type="text" name="actual_hrs_today"></td>
                    <td><input type="text" name="meter_kms_today"></td>
                    <td><input type="text" name="meter_hrs_today"></td>
                </tr>
                <tr>
                    <td>Yesterday's Accumulated:</td>
                    <td><input type="text" name="actual_kms_yesterday"></td>
                    <td><input type="text" name="actual_hrs_yesterday"></td>
                    <td><input type="text" name="meter_kms_yesterday"></td>
                    <td><input type="text" name="meter_hrs_yesterday"></td>
                    <td style="border:none;"></td>
                    <td style="border:none;"></td>
                </tr>
                <tr>
                    <td>Today's :</td>
                    <td colspan="2"><textarea name="todays" style="width:98%; height:32px; border:1px solid #333; border-radius:4px;"></textarea></td>
                    <td colspan="2">Equipment user:<input type="text" name="equipment_user1" style="width:90%; border-bottom:1px solid #333; border-radius:0;"></td>
                    <td colspan="2"></td>
                </tr>
                <tr>
                    <td>Chargeable:</td>
                    <td colspan="2"><textarea name="chargeable" style="width:98%; height:32px; border:1px solid #333; border-radius:4px;"></textarea></td>
                    <td colspan="2">Equipment user:<input type="text" name="equipment_user2" style="width:90%; border-bottom:1px solid #333; border-radius:0;"></td>
                    <td colspan="2"></td>
                </tr>
                <!-- <tr>
                    <td colspan="8" style="padding:0; border:none;">
                        <div style="margin:8px 0 8px 0;">
                            <label for="equipment_user_input" style="font-weight:bold;">Equipment User's Input/Remarks:</label><br>
                            <textarea name="equipment_user_input" id="equipment_user_input" style="width:99%; height:40px; border:1px solid #333; border-radius:4px; margin-top:4px;"></textarea>
                        </div>
                    </td>
                </tr> -->
            </table>
        </td>
    </tr>
    <tr>
        <td colspan="2" style="font-weight:bold; text-align:right; background:#f3f4f6;">Equipment User(s):</td>
        <td colspan="6">
            <input type="text" name="equipment_users" placeholder="Enter equipment user(s) name(s) here" style="width:98%; border-bottom:1px solid #333; border-radius:0;">
        </td>
    </tr>
    <tr>
        <td>Prepared by Operator:</td>
        <td colspan="2"><input type="text" name="prepared_by"></td>
        <td>Date:</td>
        <td><input type="date" name="prepared_date"></td>
        <td>Conforme by Eqpt. User:</td>
        <td><input type="text" name="conforme_by"></td>
        <td>Date:<input type="date" name="conforme_date"></td>
    </tr>
    <tr>
        <td colspan="2" style="text-align:center;">
            <input type="checkbox" name="l_check"> L
        </td>
        <td colspan="3" style="text-align:center;">
            <input type="checkbox" name="repair_needed"> REPAIR NEEDED
        </td>
        <td colspan="3" style="text-align:center;">
            <input type="checkbox" name="adjustment_needed"> ADJUSTMENT NEEDED
        </td>
    </tr>
    <!-- BEFORE OPERATION -->
    <tr>
        <th colspan="8">I. BEFORE OPERATION</th>
    </tr>
    <tr>
        <td colspan="8">
            <table style="width:100%; border:none;">
                <tr>
                    <td style="border:none; padding-bottom: 6px;">
                        <b>1. Cleaning:</b>
                        <label style="margin-right:18px;"><input type="checkbox" name="cleaning_inside"> Inside</label>
                        <label><input type="checkbox" name="cleaning_outside"> Outside</label>
                    </td>
                </tr>
                <tr>
                    <td style="border:none; padding-bottom: 6px;">
                        <b>2. Damage:</b>
                        <input type="text" name="damage" style="width: 60%; border-bottom: 1px solid #333; border-radius: 0; display:inline-block; margin-left:8px;">
                    </td>
                </tr>
                <tr>
                    <td style="border:none; padding-bottom: 6px;">
                        <b>3. Leaks:</b>
                        <label style="margin-right:12px;"><input type="checkbox" name="leaks_oils"> Oils</label>
                        <label style="margin-right:12px;"><input type="checkbox" name="leaks_water"> Water</label>
                        <label style="margin-right:12px;"><input type="checkbox" name="leaks_fuel"> Fuel</label>
                        <label><input type="checkbox" name="leaks_hydraulics"> Hydraulics</label>
                    </td>
                </tr>
                <tr>
                    <td style="border:none; padding-bottom: 6px;">
                        <b>4.</b>
                        <label style="margin-right:12px;"><input type="checkbox" name="engine_oil_level"> Engine Oil Level</label>
                        <label style="margin-right:12px;"><input type="checkbox" name="coolant_level"> Coolant Level</label>
                        <label style="margin-right:12px;"><input type="checkbox" name="tools"> Tools</label>
                        <label><input type="checkbox" name="tire_inflation"> Tire Inflation</label>
                    </td>
                </tr>
                <tr>
                    <td style="border:none; padding-bottom: 6px;">
                        <b>5. Fan Belt Tension (1/2'' to 3/4'' slack):</b>
                        <label style="margin-right:12px;"><input type="checkbox" name="fanbelt_adjust"> Adjust</label>
                        <label><input type="checkbox" name="fanbelt_checkwear"> Check Wear</label>
                    </td>
                </tr>
                <tr>
                    <td style="border:none; padding-bottom: 6px;">
                        <b>6. Battery:</b>
                        <label style="margin-right:12px;"><input type="checkbox" name="battery_terminals"> Terminals</label>
                        <label style="margin-right:12px;"><input type="checkbox" name="battery_ventholes"> Vent Holes</label>
                        <label><input type="checkbox" name="battery_liquidlevel"> Liquid Level</label>
                    </td>
                </tr>
                <tr>
                    <td style="border:none; padding-bottom: 6px;">
                        <b>7. Engine Warm-up</b>
                        <span style="margin-left:18px;">Tools:</span>
                        <input type="text" name="warmup_tools" style="width: 25%; border-bottom: 1px solid #333; border-radius: 0; display:inline-block; margin-left:6px; margin-right:18px;">
                        <span>Complete/ Missing</span>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
    <!-- DURING OPERATION -->
    <tr>
        <th colspan="8">II. DURING OPERATION</th>
    </tr>
    <tr>
        <td colspan="8">
            <table style="width:100%; border:none;">
                <tr>
                    <td style="border:none; padding-bottom: 6px;">
                        <b>1. Panel Board:</b>
                        <label style="margin-right:12px;"><input type="checkbox" name="panel_engine_oil_pressure"> Engine Oil Pressure</label>
                        <label style="margin-right:12px;"><input type="checkbox" name="panel_water_temp"> Water Temperature</label>
                        <label style="margin-right:12px;"><input type="checkbox" name="panel_fuel_level"> Fuel Level</label>
                        <label style="margin-right:12px;"><input type="checkbox" name="panel_air_pressure"> Air Pressure</label>
                        <label style="margin-right:12px;"><input type="checkbox" name="panel_torque_converter"> Torque Converter</label>
                        <label style="margin-right:12px;"><input type="checkbox" name="panel_charging_rate"> Charging Rate</label>
                        <label style="margin-right:12px;"><input type="checkbox" name="panel_speedometer"> Speedometer</label>
                        <label style="margin-right:12px;"><input type="checkbox" name="panel_tachometer"> Tachometer</label>
                        <label style="margin-right:12px;"><input type="checkbox" name="panel_air_temp"> Air Temperature</label>
                        <label style="margin-right:12px;"><input type="checkbox" name="panel_hydraulic_system"> Hydraulic System</label>
                    </td>
                </tr>
                <tr>
                    <td style="border:none; padding-bottom: 6px;">
                        <b>2. Engine Operation:</b>
                        <label style="margin-right:18px;"><input type="checkbox" name="engine_low_idle"> Low Idle</label>
                        <label><input type="checkbox" name="engine_high_idle"> High Idle</label>
                    </td>
                </tr>
                <tr>
                    <td style="border:none; padding-bottom: 6px;">
                        <b>3. Unusual Noise:</b>
                        Indicate Where (<input type="text" name="unusual_noise_where" style="width: 40%; border-bottom: 1px solid #333; border-radius: 0; display:inline-block;"> )
                        <span style="margin-left:18px; font-size:11px; color:#444;">(If getting worse, return to nearest pool)</span>
                    </td>
                </tr>
                <tr>
                    <td style="border:none; padding-bottom: 6px;">
                        <b>4. Unusual Odor:</b>
                        Indicate Where (<input type="text" name="unusual_odor_where" style="width: 40%; border-bottom: 1px solid #333; border-radius: 0; display:inline-block;"> )
                        <span style="margin-left:18px; font-size:11px; color:#444;">(If getting worse, return to nearest pool)</span>
                    </td>
                </tr>
                <tr>
                    <td style="border:none; padding-bottom: 6px;">
                        <span style="font-weight:bold; color:#b91c1c;">CAUTION: DO NOT DUMP WATER INTO HOT ENGINES; ADD WATER SLOWLY WITH ENGINE IDLING.</span>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
    <!-- AFTER OPERATION -->
    <tr>
        <th colspan="8">III. AFTER OPERATION</th>
    </tr>
    <tr>
        <td colspan="8">
            <table style="width:100%; border:none;">
                <tr>
                    <td style="border:none; padding-bottom: 6px;">
                        <b>1. Safety Devices:</b>
                        <label style="margin-right:12px;"><input type="checkbox" name="after_safety_selector"> Safety Selector</label>
                        <label style="margin-right:12px;"><input type="checkbox" name="after_brake_pedal_lock"> Brake pedal lock</label>
                        <label style="margin-right:12px;"><input type="checkbox" name="after_horns"> Horns</label>
                        <label style="margin-right:12px;"><input type="checkbox" name="after_hitches"> Hitches</label>
                        <label style="margin-right:12px;"><input type="checkbox" name="after_safety_switch_off"> Safety Switch 'OFF'</label>
                        <label style="margin-right:12px;"><input type="checkbox" name="after_levers_links"> Levers and Links</label>
                    </td>
                </tr>
                <tr>
                    <td style="border:none; padding-bottom: 6px;">
                        <b>2. Track Tension</b> <span style="font-size:11px;">(3/4" to 1' Slack)</span>
                    </td>
                </tr>
                <tr>
                    <td style="border:none; padding-bottom: 6px;">
                        <b>3. Air Cleaner:</b>
                        <label style="margin-right:12px;"><input type="checkbox" name="after_air_service"> Service</label>
                        <label style="margin-right:12px;"><input type="checkbox" name="after_air_clean"> Clean</label>
                        <label style="margin-right:12px;"><input type="checkbox" name="after_air_oil_level"> Oil Level (Wet Type)</label>
                    </td>
                </tr>
                <tr>
                    <td style="border:none; padding-bottom: 6px;">
                        <b>4. Track Shoe Bolts:</b>
                        <label style="margin-right:12px;"><input type="checkbox" name="after_track_tighten"> Tighten</label>
                        <label style="margin-right:12px;"><input type="checkbox" name="after_cutting_edge"> Cutting Edge</label>
                        <label style="margin-right:12px;"><input type="checkbox" name="after_end_bits"> End Bits</label>
                        <label style="margin-right:12px;"><input type="checkbox" name="after_diagonal_brace"> Diagonal Brace</label>
                    </td>
                </tr>
                <tr>
                    <td style="border:none; padding-bottom: 6px;">
                        <b>5. Park Safely;</b> Lower Blade/ Bloom; Record KM/HR RDG & Fuel Consumed
                    </td>
                </tr>
                <tr>
                    <td style="border:none; padding-bottom: 6px;">
                        <b>6. Period of Operation/ Use:</b> From <input type="text" name="after_period_from" style="width:80px; border-bottom:1px solid #333; border-radius:0; display:inline-block;"> AM/PM to <input type="text" name="after_period_to" style="width:80px; border-bottom:1px solid #333; border-radius:0; display:inline-block;"> AM/PM
                    </td>
                </tr>
                <tr>
                    <td style="border:none; padding-bottom: 6px;">
                        <b>7. Cleaning:</b>
                        <label style="margin-right:12px;"><input type="checkbox" name="after_cleaning_inside"> Inside</label>
                        <label style="margin-right:12px;"><input type="checkbox" name="after_cleaning_outside"> Outside</label>
                        <label style="margin-right:12px;"><input type="checkbox" name="after_defects_noticed"> Defects Noticed</label>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
    
    <!-- NOTES -->
    <tr>
        <th colspan="8">IV. NOTES / ACTION TAKEN BY OPERATOR / INSPECTOR</th>
    </tr>
    <tr>
        <td colspan="8">
            <table style="width:100%; border:none;">
                <tr>
                    <td style="border:none; padding-bottom: 6px;">
                        <b>1. Notes / Observations:</b><br>
                        <textarea name="notes_observations" style="width:98%; height:40px; border:1px solid #333; border-radius:4px; margin-top:4px;"></textarea>
                    </td>
                </tr>
                <tr>
                    <td style="border:none; padding-bottom: 6px;">
                        <b>2. Action(s) Taken:</b><br>
                        <textarea name="notes_actions" style="width:98%; height:40px; border:1px solid #333; border-radius:4px; margin-top:4px;"></textarea>
                    </td>
                </tr>
                <tr>
                    <td style="border:none; padding-bottom: 6px;">
                        <b>3. Name & Signature of Operator/Inspector:</b>
                        <input type="text" name="notes_operator" style="width:60%; border-bottom:1px solid #333; border-radius:0; display:inline-block; margin-left:8px;">
                    </td>
                </tr>
                <tr>
                    <td style="border:none; padding-bottom: 6px;">
                        <b>4. Date:</b>
                        <input type="date" name="notes_date" style="width:30%; border-bottom:1px solid #333; border-radius:0; display:inline-block; margin-left:8px;">
                    </td>
                </tr>
                <tr>
                    <td style="border:none; padding-bottom: 6px;">
                        <b>Operational Date:</b>
                        <input type="date" name="notes_operational_date" style="width:180px; border-bottom:1px solid #333; border-radius:0; display:inline-block; margin-left:8px; margin-right:32px;">
                        <b>Record of Work:</b>
                        <input type="text" name="notes_record_of_work" style="width:220px; border-bottom:1px solid #333; border-radius:0; display:inline-block; margin-left:8px;">
                    </td>
                </tr>
            </table>
        </td>
    </tr>
    <!-- FUEL TABLE -->
    <tr>
        <td colspan="8" style="padding:0; border:none;">
            <table style="width:100%; border-collapse:collapse;">
                <tr>
                    <th style="min-width:120px;">FUEL AND OIL USED</th>
                    <th style="min-width:60px;">QTY</th>
                    <th style="min-width:70px;">COST</th>
                    <th style="min-width:100px;">LOCATION</th>
                    <th style="min-width:120px;">NATURE/DETAILS</th>
                    <th style="min-width:120px;">ACCOMPLISHMENT<br>(QTY)</th>
                    <th style="min-width:120px;">ACTUAL<br>OPERATING HOURS</th>
                </tr>
                <tr>
                    <td>Diesel/Gasoline</td>
                    <td><input type="text" name="diesel_qty" style="width:100%;"></td>
                    <td><input type="text" name="diesel_cost" style="width:100%;"></td>
                    <td><input type="text" name="diesel_location" style="width:100%;"></td>
                    <td><input type="text" name="diesel_details" style="width:100%;"></td>
                    <td><input type="text" name="diesel_accomplishment" style="width:100%;"></td>
                    <td><input type="text" name="diesel_hours" style="width:100%;"></td>
                </tr>
                <tr>
                    <td>Engine Oil</td>
                    <td><input type="text" name="engine_oil_qty" style="width:100%;"></td>
                    <td><input type="text" name="engine_oil_cost" style="width:100%;"></td>
                    <td><input type="text" name="engine_oil_location" style="width:100%;"></td>
                    <td><input type="text" name="engine_oil_details" style="width:100%;"></td>
                    <td><input type="text" name="engine_oil_accomplishment" style="width:100%;"></td>
                    <td><input type="text" name="engine_oil_hours" style="width:100%;"></td>
                </tr>
                <tr>
                    <td>Hydraulic Oil</td>
                    <td><input type="text" name="hydraulic_oil_qty" style="width:100%;"></td>
                    <td><input type="text" name="hydraulic_oil_cost" style="width:100%;"></td>
                    <td><input type="text" name="hydraulic_oil_location" style="width:100%;"></td>
                    <td><input type="text" name="hydraulic_oil_details" style="width:100%;"></td>
                    <td><input type="text" name="hydraulic_oil_accomplishment" style="width:100%;"></td>
                    <td><input type="text" name="hydraulic_oil_hours" style="width:100%;"></td>
                </tr>
                <tr>
                    <td>Gear Oil</td>
                    <td><input type="text" name="gear_oil_qty" style="width:100%;"></td>
                    <td><input type="text" name="gear_oil_cost" style="width:100%;"></td>
                    <td><input type="text" name="gear_oil_location" style="width:100%;"></td>
                    <td><input type="text" name="gear_oil_details" style="width:100%;"></td>
                    <td><input type="text" name="gear_oil_accomplishment" style="width:100%;"></td>
                    <td><input type="text" name="gear_oil_hours" style="width:100%;"></td>
                </tr>
                <tr>
                    <td>Grease</td>
                    <td><input type="text" name="grease_qty" style="width:100%;"></td>
                    <td><input type="text" name="grease_cost" style="width:100%;"></td>
                    <td><input type="text" name="grease_location" style="width:100%;"></td>
                    <td><input type="text" name="grease_details" style="width:100%;"></td>
                    <td><input type="text" name="grease_accomplishment" style="width:100%;"></td>
                    <td><input type="text" name="grease_hours" style="width:100%;"></td>
                </tr>
                <tr>
                    <td>Others</td>
                    <td><input type="text" name="others_qty" style="width:100%;"></td>
                    <td><input type="text" name="others_cost" style="width:100%;"></td>
                    <td><input type="text" name="others_location" style="width:100%;"></td>
                    <td><input type="text" name="others_details" style="width:100%;"></td>
                    <td><input type="text" name="others_accomplishment" style="width:100%;"></td>
                    <td><input type="text" name="others_hours" style="width:100%;"></td>
                </tr>
                <tr>
                    <td><b>TOTAL:</b></td>
                    <td><input type="text" name="total_qty" style="width:100%; font-weight:bold;"></td>
                    <td><input type="text" name="total_cost" style="width:100%; font-weight:bold;"></td>
                    <td><input type="text" name="total_location" style="width:100%; font-weight:bold;"></td>
                    <td><input type="text" name="total_details" style="width:100%; font-weight:bold;"></td>
                    <td><input type="text" name="total_accomplishment" style="width:100%; font-weight:bold;"></td>
                    <td><input type="text" name="total_hours" style="width:100%; font-weight:bold;"></td>
                </tr>
            </table>
        </td>
    </tr>
</table>
<div style="text-align:center; margin-top:18px;">
    <button type="submit" style="padding:8px 32px; font-size:1.1em; border-radius:8px; background:#22c55e; color:#fff; border:none; font-weight:600;">Submit</button>
</div>
</form>

</body>
</html>