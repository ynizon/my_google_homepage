<div class="modal" tabindex="-1" role="dialog" id="addModal">
    <form id="frmAddModal">
        {{csrf_field()}}
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h1 class="modal-title">Réglage de l'alarme</h1>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-sm-6">
                            <input id="alarm_id" type="hidden" value="0" name="alarm_id">
                            <span id="status-block">
                                <label for="status">
                                    Activée
                                </label>
                                <input id="status" type="checkbox" value="1" name="status[]">
                                &nbsp;&nbsp;&nbsp;
                            </span>
                            <select name="hour" id="hour" class="custom-select">
                                @for ($hour = 0; $hour < 24; $hour++)
                                    <option @if ($hour == 7) selected @endif value="{{$hour}}">{{sprintf("%02d",$hour)}}</option>
                                @endfor
                            </select>
                             :
                            <select name="minute" id="minute" class="custom-select">
                                @for ($minute = 0; $minute < 60; $minute = $minute + 1 )
                                    <option value="{{$minute}}">{{sprintf("%02d",$minute)}}</option>
                                @endfor
                            </select>
                        </div>
                        <div class="col-sm-6">
                            <select name="sound" id="sound" class="custom-select">
                                <optgroup label="Radios">
                                    @foreach($radios as $radio => $url)
                                        <option value="{{$url}}">{{$radio}}</option>
                                    @endforeach
                                </optgroup>
                                <optgroup label="Sons">
                                    @foreach($sounds as $sound => $url)
                                        <option value="{{$url}}">{{$sound}}</option>
                                    @endforeach
                                </optgroup>
                            </select>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <br/>
                            <table class="table clock-table">
                                <tr>
                                    <td>
                                        <label for="day1">
                                            Lundi
                                        </label>
                                        <br/>
                                        <input id="day1" type="checkbox" value="1" name="day[]">
                                    </td>
                                    <td>
                                        <label for="day2">
                                            Mardi
                                        </label>
                                        <br/>
                                        <input id="day2" type="checkbox" value="2" name="day[]">
                                    </td>
                                    <td>
                                        <label for="day3">
                                            Mercredi
                                        </label>
                                        <br/>
                                        <input id="day3" type="checkbox" value="3" name="day[]">
                                    </td>
                                    <td>
                                        <label for="day4">
                                            Jeudi
                                        </label>
                                        <br/>
                                        <input id="day4" type="checkbox" value="4" name="day[]">
                                    </td>
                                    <td>
                                        <label for="day5">
                                            Vendredi
                                        </label>
                                        <br/>
                                        <input id="day5" type="checkbox" value="5" name="day[]">
                                    </td>
                                    <td>
                                        <label for="day6">
                                            Samedi
                                        </label>
                                        <br/>
                                        <input id="day6" type="checkbox" value="6" name="day[]">
                                    </td>
                                    <td>
                                        <label for="day7">
                                            Dimanche
                                        </label>
                                        <br/>
                                        <input id="day7" type="checkbox" value="7" name="day[]">
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <span style="text-align: left;float:left">
                        <button id="deleteAlarm" type="button" class="btn btn-danger" data-dismiss="modal">Supprimer</button>
                    </span>
                    <span >
                        <button id="addAlarm" type="button" class="btn btn-primary" data-dismiss="modal">Enregistrer</button>
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Annuler</button>
                    </span>
                </div>
            </div>
        </div>
    </form>
</div>
