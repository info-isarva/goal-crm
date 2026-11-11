<script>
(function () {
    // Exact same statesByCountry map as quotation address
    var statesByCountry = {
        'India': ['Andhra Pradesh', 'Arunachal Pradesh', 'Assam', 'Bihar', 'Chhattisgarh', 'Goa', 'Gujarat', 'Haryana', 'Himachal Pradesh', 'Jharkhand', 'Karnataka', 'Kerala', 'Madhya Pradesh', 'Maharashtra', 'Manipur', 'Meghalaya', 'Mizoram', 'Nagaland', 'Odisha', 'Punjab', 'Rajasthan', 'Sikkim', 'Tamil Nadu', 'Telangana', 'Tripura', 'Uttar Pradesh', 'Uttarakhand', 'West Bengal', 'Delhi'],
        'United Arab Emirates': ['Abu Dhabi', 'Dubai', 'Sharjah', 'Ajman', 'Umm Al Quwain', 'Ras Al Khaimah', 'Fujairah'],
        'UAE': ['Abu Dhabi', 'Dubai', 'Sharjah', 'Ajman', 'Umm Al Quwain', 'Ras Al Khaimah', 'Fujairah'],
        'United States': ['Alabama', 'Alaska', 'Arizona', 'Arkansas', 'California', 'Colorado', 'Connecticut', 'Delaware', 'Florida', 'Georgia', 'Hawaii', 'Idaho', 'Illinois', 'Indiana', 'Iowa', 'Kansas', 'Kentucky', 'Louisiana', 'Maine', 'Maryland', 'Massachusetts', 'Michigan', 'Minnesota', 'Mississippi', 'Missouri', 'Montana', 'Nebraska', 'Nevada', 'New Hampshire', 'New Jersey', 'New Mexico', 'New York', 'North Carolina', 'North Dakota', 'Ohio', 'Oklahoma', 'Oregon', 'Pennsylvania', 'Rhode Island', 'South Carolina', 'South Dakota', 'Tennessee', 'Texas', 'Utah', 'Vermont', 'Virginia', 'Washington', 'West Virginia', 'Wisconsin', 'Wyoming'],
        'Canada': ['Alberta', 'British Columbia', 'Manitoba', 'New Brunswick', 'Newfoundland and Labrador', 'Nova Scotia', 'Ontario', 'Prince Edward Island', 'Quebec', 'Saskatchewan'],
        'Australia': ['New South Wales', 'Queensland', 'South Australia', 'Tasmania', 'Victoria', 'Western Australia', 'Australian Capital Territory', 'Northern Territory'],
        'United Kingdom': ['England', 'Scotland', 'Wales', 'Northern Ireland'],
        'Saudi Arabia': ['Riyadh', 'Makkah', 'Madinah', 'Eastern Province', 'Asir', 'Tabuk', 'Qassim', 'Hail', 'Jizan', 'Najran', 'Northern Borders', 'El-Jauf', 'Al-Baha'],
        'Qatar': ['Doha', 'Al Rayyan', 'Al Wakrah', 'Al Khor', 'Umm Salal', 'Al Daayen', 'Madinat ash Shamal', 'Shahaniya'],
        'Kuwait': ['Al Asimah (Kuwait City)', 'Hawalli', 'Farwaniya', 'Ahmadi', 'Jahra', 'Mubarak Al-Kabeer'],
        'Bahrain': ['Capital Governorate', 'Muharraq Governorate', 'Northern Governorate', 'Southern Governorate'],
        'Oman': ['Muscat', 'Dhofar', 'Musandam', 'Al Buraimi', 'Ad Dakhiliyah', 'North Al Batinah', 'South Al Batinah', 'North Ash Sharqiyah', 'South Ash Sharqiyah', 'Ad Dhahirah', 'Al Wusta'],
        'Germany': ['Baden-Württemberg', 'Bavaria', 'Berlin', 'Brandenburg', 'Bremen', 'Hamburg', 'Hesse', 'Lower Saxony', 'Mecklenburg-Vorpommern', 'North Rhine-Westphalia', 'Rhineland-Palatinate', 'Saarland', 'Saxony', 'Saxony-Anhalt', 'Schleswig-Holstein', 'Thuringia'],
        'France': ['Île-de-France', 'Auvergne-Rhône-Alpes', 'Bourgogne-Franche-Comté', 'Brittany', 'Centre-Val de Loire', 'Corsica', 'Grand Est', 'Hauts-de-France', 'Normandy', 'Nouvelle-Aquitaine', 'Occitanie', 'Pays de la Loire', 'Provence-Alpes-Côte d\'Azur'],
        'Singapore': ['Central Region', 'East Region', 'North Region', 'North-East Region', 'West Region'],
        'Japan': ['Hokkaido', 'Tohoku', 'Kanto (Tokyo)', 'Chubu', 'Kansai (Osaka)', 'Chugoku', 'Shikoku', 'Kyushu & Okinawa'],
        'China': ['Beijing', 'Shanghai', 'Guangdong', 'Zhejiang', 'Jiangsu', 'Sichuan', 'Shandong', 'Hubei', 'Fujian', 'Henan'],
        'Switzerland': ['Zurich', 'Bern', 'Lucerne', 'Uri', 'Schwyz', 'Obwalden', 'Nidwalden', 'Glarus', 'Zug', 'Fribourg', 'Solothurn', 'Basel-Stadt', 'Basel-Landschaft', 'Schaffhausen', 'Appenzell Ausserrhoden', 'Appenzell Innerrhoden', 'St. Gallen', 'Graubünden', 'Aargau', 'Thurgau', 'Ticino', 'Vaud', 'Valais', 'Neuchâtel', 'Geneva', 'Jura'],
        'Malaysia': ['Johor', 'Kedah', 'Kelantan', 'Melaka', 'Negeri Sembilan', 'Pahang', 'Penang', 'Perak', 'Perlis', 'Sabah', 'Sarawak', 'Selangor', 'Terengganu', 'Kuala Lumpur', 'Labuan', 'Putrajaya'],
        'New Zealand': ['Auckland', 'Bay of Plenty', 'Canterbury', 'Gisborne', 'Hawke\'s Bay', 'Manawatu-Whanganui', 'Marlborough', 'Nelson', 'Northland', 'Otago', 'Southland', 'Taranaki', 'Tasman', 'Waikato', 'Wellington', 'West Coast'],
        'South Africa': ['Gauteng', 'Western Cape', 'KwaZulu-Natal', 'Eastern Cape', 'Free State', 'Limpopo', 'Mpumalanga', 'North West', 'Northern Cape']
    };

    function populateOrgStates(country, selected) {
        var stateSelect = document.getElementById('org_state');
        if (!stateSelect) return;
        stateSelect.innerHTML = '';
        var placeholder = document.createElement('option');
        placeholder.value = '';
        placeholder.text = '-None-';
        stateSelect.appendChild(placeholder);
        var list = statesByCountry[country] || [];
        if (list.length > 0) {
            list.forEach(function (s) {
                var opt = document.createElement('option');
                opt.value = s;
                opt.text = s;
                if (selected && selected === s) opt.selected = true;
                stateSelect.appendChild(opt);
            });
            stateSelect.disabled = false;
        } else {
            var prev = selected || '';
            if (prev) {
                var opt = document.createElement('option');
                opt.value = prev;
                opt.text = prev;
                opt.selected = true;
                stateSelect.appendChild(opt);
            } else {
                stateSelect.querySelector('option').text = 'No states available';
            }
            stateSelect.disabled = false;
        }
    }

    window.populateOrgModalStates = populateOrgStates;

    document.addEventListener('DOMContentLoaded', function () {
        var countryEl = document.getElementById('org_country');
        if (!countryEl) return;

        populateOrgStates(countryEl.value || 'India', '');
        countryEl.addEventListener('change', function () {
            populateOrgStates(this.value, '');
        });

        var orgModal = document.getElementById('orgNewModal');
        if (orgModal) {
            orgModal.addEventListener('shown.bs.modal', function () {
                var c = document.getElementById('org_country');
                if (c && !c.value) c.value = 'India';
                populateOrgStates((c && c.value) || 'India', '');
            });
            orgModal.addEventListener('hidden.bs.modal', function () {
                var form = document.getElementById('orgNewForm');
                if (form) form.reset();
                var c = document.getElementById('org_country');
                if (c) c.value = 'India';
                populateOrgStates('India', '');
            });
        }
    });
})();
</script>
