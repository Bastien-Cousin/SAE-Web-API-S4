<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>prediction</title>
    <link rel="stylesheet" href="Predictions.css">
    <link rel="stylesheet" href="../../components/ComponentNavbar/navbar.css">
    <link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/tom-select/dist/css/tom-select.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/tom-select/dist/js/tom-select.complete.min.js"></script>
</head>

<body>
    <?php include '../../components/ComponentNavbar/navbar.php'; ?>

    <nav>
        <a href="#live" class="active">Live</a>
        <a href="#compo">Composition</a>
        <a href="#cal">Calendrier</a>
        <a href="#pred">Prédiction</a>
    </nav>

    <div class="wrapper">

        <div class="section-title" id="live">Live</div>

        <div class="match-card">
            <div class="team-side left">
                <div class="team-logo">logo</div>
                <div class="team-name">FaZe</div>
            </div>
            <div class="score-block">
                <span class="score">0</span>
                <span class="sep">–</span>
                <span class="score winning">11</span>
            </div>
            <div class="team-side right">
                <div class="team-logo">logo</div>
                <div class="team-name">Vitality</div>
            </div>
        </div>

        <div class="section-title" id="compo">Votre composition</div>

        <div class="filters">
            <select id="team1">
                <option value="">Équipe 1</option>
                <option value="FaZe">FaZe</option>
                <option value="Vitality">Vitality</option>
                <option value="Liquid">Liquid</option>
                <option value="NAVI">NAVI</option>
            </select>

            <select id="team2">
                <option value="">Équipe 2</option>
                <option value="FaZe">FaZe</option>
                <option value="Vitality">Vitality</option>
                <option value="Liquid">Liquid</option>
                <option value="NAVI">NAVI</option>
            </select>

            <select id="bo">
                <option value="">Best of</option>
                <option value="BO1">BO1</option>
                <option value="BO3">BO3</option>
                <option value="BO5">BO5</option>
            </select>

            <input type="time" id="heure">

            <select id="map">
                <option value="">Map</option>
                <option value="Mirage">Mirage</option>
                <option value="Inferno">Inferno</option>
                <option value="Nuke">Nuke</option>
            </select>
        </div>

        <div class="comp-grid">
            <div class="team-box left">équipe 1</div>

            <div class="vs-block">
                <div class="bestof-badge">Best of</div>
                <div class="heure-badge">heure</div>
            </div>

            <div class="team-box right">équipe 2</div>
        </div>

        <div class="stats-row">
            <div class="stats-side left-stats">
                <div class="stat-tag">WR</div>
                <div class="stat-tag">K/D</div>
            </div>

            <div class="stats-side right-stats">
                <div class="stat-tag">WR</div>
                <div class="stat-tag">K/D</div>
            </div>
        </div>

        <div class="comp-footer">
            <span class="maps-label">maps</span>
            <button onclick="window.location.href='userPrediction.php'">
                Lancer
            </button>
        </div>


        <div class="section-title" id="cal">Calendrier</div>

        <div class="calendar-box">
            <div id="calendar"></div>
        </div>

        <div class="section-title" id="pred">Vos prédictions</div>

        <div class="pred-card wrong">
            <div class="pred-label">Préd.</div>
            <div class="pred-scores">
                <div class="pred-score">12–6</div>
            </div>
            <div class="pred-logos">log1 log2 </div>
            <div class="real-label">Réalité</div>
            <div class="real-scores">
                <div class="real-score">8–12</div>
            </div>
        </div>

    </div>

    <script>
        document.querySelectorAll('nav a').forEach(link => {
            link.addEventListener('click', () => {
                document.querySelectorAll('nav a').forEach(a => a.classList.remove('active'));
                link.classList.add('active');
            });
        });


        const stats = {
            "FaZe": {
                wr: "60%",
                kd: "1.10"
            },
            "Vitality": {
                wr: "75%",
                kd: "1.25"
            },
            "Liquid": {
                wr: "55%",
                kd: "1.05"
            },
            "NAVI": {
                wr: "70%",
                kd: "1.20"
            }
        };

        // ELEMENTS
        const team1 = document.getElementById("team1");
        const team2 = document.getElementById("team2");
        const bo = document.getElementById("bo");
        const heure = document.getElementById("heure");
        const map = document.getElementById("map");

        const teamBox1 = document.querySelector(".team-box.left");
        const teamBox2 = document.querySelector(".team-box.right");
        const boBadge = document.querySelector(".bestof-badge");
        const heureBadge = document.querySelector(".heure-badge");
        const mapsLabel = document.querySelector(".maps-label");

        const leftStats = document.querySelector(".left-stats");
        const rightStats = document.querySelector(".right-stats");

        team1.onchange = () => {
            teamBox1.textContent = team1.value || "équipe 1";

            if (stats[team1.value]) {
                leftStats.innerHTML = `
                    <div class="stat-tag">WR ${stats[team1.value].wr}</div>
                    <div class="stat-tag">K/D ${stats[team1.value].kd}</div>
                `;
            }
        };

        team2.onchange = () => {
            teamBox2.textContent = team2.value || "équipe 2";

            if (stats[team2.value]) {
                rightStats.innerHTML = `
                    <div class="stat-tag">WR ${stats[team2.value].wr}</div>
                    <div class="stat-tag">K/D ${stats[team2.value].kd}</div>
                `;
            }
        };

        bo.onchange = () => {
            boBadge.textContent = bo.value || "Best of";
        };

        heure.onchange = () => {
            heureBadge.textContent = heure.value || "heure";
        };

        map.onchange = () => {
            mapsLabel.textContent = map.value || "maps";
        };

        // CALENDAR
        document.getElementById("calendar").addEventListener("change", (e) => {
            console.log("Date choisie :", e.target.value);
        });
        document.addEventListener('DOMContentLoaded', function() {

            const calendarEl = document.getElementById('calendar');

            const calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'dayGridMonth',

                locale: 'fr',

                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth,timeGridWeek'
                },

                events: [{
                        title: 'FaZe vs Vitality',
                        date: '2026-04-02'
                    },
                    {
                        title: 'NAVI vs Liquid',
                        date: '2026-04-05'
                    }
                ],

                dateClick: function(info) {
                    alert("Date sélectionnée : " + info.dateStr);
                }
            });

            calendar.render();
        });
        new TomSelect("#team1", {
            create: false,
            sortField: {
                field: "text",
                direction: "asc"
            }
        });

        new TomSelect("#team2", {});
        new TomSelect("#bo", {});
        new TomSelect("#map", {});

        //fetch("https://api.tonsite.com/teams")
        //     .then(res => res.json())
        //     .then(data => {
        //         const select = document.getElementById("team1");

        //         data.forEach(team => {
        //             const option = document.createElement("option");
        //             option.value = team.name;
        //             option.textContent = team.name;
        //             select.appendChild(option);
        //         });

        //         new TomSelect("#team1");
        //     });
        // 
    </script>


</body>

</html>