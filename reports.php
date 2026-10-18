<?php
require_once __DIR__ . '/auth.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <link rel="icon" type="image/png" href="assets/clinic-logo.png">
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Analytics & Reports - Urban Fur Veterinary Clinic</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="style.css?v=36">
</head>
<body>

  <!-- Navigation Sidebar -->
  <aside class="sidebar">
    <div class="sidebar-brand"><i class="fa-solid fa-paw"></i> Urban Fur</div>
    
    <ul class="nav-list">
      <li class="nav-item"><a href="index.php"><i class="fa-solid fa-chart-pie"></i> Dashboard</a></li>
      <li class="nav-item"><a href="pets.php"><i class="fa-solid fa-paw"></i> Patients (Pets)</a></li>
      <li class="nav-item"><a href="appointments.php"><i class="fa-regular fa-calendar-check"></i> Appointments</a></li>
      <li class="nav-item"><a href="clients.php"><i class="fa-solid fa-users"></i> Clients</a></li>
      <li class="nav-item"><a href="services.php"><i class="fa-solid fa-hand-holding-medical"></i> Services</a></li>
      <li class="nav-item"><a href="staff.php"><i class="fa-solid fa-user-doctor"></i> Staff</a></li>
      <li class="nav-item"><a href="inventory.php"><i class="fa-solid fa-boxes-stacked"></i> Inventory</a></li>
      <li class="nav-item"><a href="billing.php"><i class="fa-solid fa-file-invoice-dollar"></i> Billing</a></li>
      <li class="nav-item active"><a href="reports.php"><i class="fa-solid fa-chart-line"></i> Reports</a></li>
      <li class="nav-item"><a href="settings.php"><i class="fa-solid fa-gear"></i> Settings</a></li>
    </ul>

  </aside>

  <main class="main-content reports-page">
    <div class="page-header reports-page-header">
      <div class="page-title">
        <span class="reports-eyebrow"><i class="fa-solid fa-chart-simple"></i> Clinic overview</span>
        <h1>Reports & Analytics</h1>
        <p>Review clinic sales, outstanding balances, and operational records.</p>
      </div>
    </div>

    <div class="stats-row reports-summary">
      <div class="stat-card report-metric report-metric-sales"><span class="stat-icon"><i class="fa-solid fa-peso-sign"></i></span><div class="stat-info"><h3 id="report-weekly-sales">₱0.00</h3><p>Paid Sales in Range</p></div></div>
      <div class="stat-card report-metric report-metric-transactions"><span class="stat-icon"><i class="fa-solid fa-receipt"></i></span><div class="stat-info"><h3 id="report-transactions">0</h3><p>Transactions in Range</p></div></div>
      <div class="stat-card report-metric report-metric-credit"><span class="stat-icon"><i class="fa-solid fa-clock"></i></span><div class="stat-info"><h3 id="report-credit">₱0.00</h3><p>Pay Later Balance in Range</p></div></div>
      <div class="stat-card report-metric report-metric-records"><span class="stat-icon"><i class="fa-solid fa-chart-line"></i></span><div class="stat-info"><h3 id="report-monthly-sales">0</h3><p>Records in Report</p></div></div>
    </div>

    <section class="report-panel report-generator" id="reportGenerator">
      <div class="report-generator-copy">
        <span class="report-generator-icon"><i class="fa-solid fa-file-lines"></i></span>
        <div><h2>Build a report</h2></div>
      </div>
      <section class="report-calendar-filter" id="reportCalendarFilter" aria-label="Optional report date range">
        <label class="report-range-toggle"><input type="checkbox" id="useReportDateRange"> Limit report to a date range</label>
        <div class="report-date-controls" id="reportDateControls">
        <div class="report-panel-heading report-calendar-title">
          <span class="report-panel-icon"><i class="fa-regular fa-calendar"></i></span>
          <div><h2>Date range</h2><p>Select a period or choose a custom range.</p></div>
        </div>
        <label>
          <span>Period</span>
          <select id="reportPeriod" class="filter-select">
            <option value="daily">Daily</option>
            <option value="weekly">Weekly</option>
            <option value="monthly" selected>Monthly</option>
            <option value="custom">Custom date range</option>
          </select>
        </label>
        <label id="reportDayControl"><span>Date</span><input type="date" id="reportDay" class="date-filter"></label>
        <label id="reportMonthControl"><span>Month</span><input type="month" id="reportMonth" class="date-filter"></label>
        <label id="reportWeekControl" hidden><span>Week of</span><input type="date" id="reportWeek" class="date-filter"></label>
        <div id="reportCustomControl" class="report-custom-range" hidden>
          <label><span>From</span><input type="date" id="reportStartDate" class="date-filter"></label>
          <label><span>To</span><input type="date" id="reportEndDate" class="date-filter"></label>
        </div>
        <button class="btn-primary report-apply-button" type="button" id="applyReportDateButton"><i class="fa-solid fa-filter"></i> Apply dates</button>
        </div>
      </section>
      <div class="report-generator-actions">
        <label class="visually-hidden" for="reportType">Report type</label>
        <select id="reportType" class="filter-select">
          <option value="sales">Sales Report</option>
          <option value="inventory">Inventory Report</option>
          <option value="clients">Clients Report</option>
          <option value="patients">Patients Report</option>
          <option value="appointments">Appointments Report</option>
          <option value="credit">Pay Later / Credit Report</option>
          <option value="expenses">Expenses Report</option>
          <option value="end-of-day">End-of-Day Report</option>
        </select>
        <button class="btn-primary" type="button" id="closeOperationsButton" hidden><i class="fa-solid fa-lock"></i> Close operations &amp; generate report</button>
        <button class="btn-secondary" type="button" id="exportReportButton" disabled><i class="fa-solid fa-file-csv"></i> Export CSV</button>
      </div>
    </section>
    <section class="report-panel report-results" aria-label="Report results">
      <div class="report-results-heading">
        <div><h2>Report results</h2><p>Results for the selected dataset and date range.</p></div>
        <button class="btn-secondary report-print-button" type="button" id="printReportButton"><i class="fa-solid fa-print"></i> Print Report</button>
      </div>
      <header class="report-print-header" id="reportPrintHeader" hidden><h1>Urban Fur Veterinary Clinic</h1><h2 id="printReportTitle"></h2><p id="printReportRange"></p><p id="printGeneratedAt"></p></header>
      <div class="table-container report-table-shell">
        <table>
          <thead id="reportTableHead"></thead>
          <tbody id="reportTableBody"><tr><td>Loading report data...</td></tr></tbody>
        </table>
      </div>
    </section>
  </main>
  <script src="assets/app.js?v=14"></script>
  <script>
    const reportDefinitions = {
      sales: {
        resource: 'invoices',
        columns: [
          ['id', 'Invoice'], ['date', 'Date'], ['client', 'Client'], ['pet', 'Patient'],
          ['payment_method', 'Payment Method'], ['status', 'Status'], ['amount', 'Amount (₱)']
        ],
        map: rows => rows.map(invoice => ({ ...invoice, amount: Number(invoice.amount).toFixed(2) }))
      },
      inventory: {
        resource: 'inventory',
        columns: [
          ['sku', 'SKU'], ['name', 'Item'], ['category', 'Category'], ['quantity', 'Units in Stock'],
          ['min_threshold', 'Minimum Stock'], ['price', 'Unit Price (₱)']
        ],
        map: rows => rows.map(item => ({ ...item, price: Number(item.price).toFixed(2) })),
        dateKey: 'created_at'
      },
      clients: {
        resource: 'clients',
        columns: [['name', 'Client'], ['phone', 'Phone'], ['email', 'Email'], ['status', 'Status'], ['pet_count', 'Patients']],
        map: rows => rows,
        dateKey: 'created_at'
      },
      patients: {
        resource: 'pets',
        columns: [['pet_code', 'Pet ID'], ['name', 'Patient'], ['species', 'Species'], ['breed', 'Breed'], ['owner', 'Owner'], ['status', 'Status']],
        map: rows => rows,
        dateKey: 'created_at'
      },
      appointments: {
        resource: 'appointments',
        columns: [['appointment_code', 'Appointment ID'], ['date_time', 'Date & Time'], ['owner_name', 'Client'], ['pet_code', 'Pet ID'], ['pet_name', 'Pet'], ['service', 'Services'], ['vet', 'Veterinarian'], ['status', 'Status']],
        map: rows => rows,
        dateKey: 'raw_date'
      },
      credit: {
        resource: 'invoices',
        columns: [
          ['id', 'Invoice'], ['date', 'Date'], ['client', 'Client'], ['pet', 'Patient'],
          ['service', 'Items'], ['status', 'Status'], ['amount', 'Balance (₱)']
        ],
        map: rows => rows
          .filter(invoice => invoice.payment_method === 'Pay Later' && ['Pending', 'Overdue'].includes(invoice.status))
          .map(invoice => ({ ...invoice, amount: Number(invoice.amount).toFixed(2) })),
        dateKey: 'date'
      },
      expenses: {
        resource: 'expenses',
        columns: [
          ['incurred_on', 'Date'], ['reason', 'Reason'], ['description', 'Details'],
          ['status', 'Status'], ['amount', 'Amount (₱)']
        ],
        map: rows => rows.map(expense => ({ ...expense, amount: Number(expense.amount).toFixed(2) })),
        dateKey: 'incurred_on'
      },
      'end-of-day': {
        resource: 'end-of-day-reports',
        columns: [
          ['closed_on', 'Operation Date'], ['official_closing_time', 'Official Closing Time'],
          ['activity', 'Activity'], ['reference', 'Reference'], ['details', 'Details'],
          ['status', 'Status'], ['amount', 'Amount (₱)']
        ],
        map: reports => reports.flatMap(report => {
          let data = report.report;
          if (!data) {
            try { data = JSON.parse(report.report_json); } catch { throw new Error(`Stored closing report ${report.id} is invalid.`); }
          }
          const rows = [];
          for (const [key, value] of Object.entries(data.totals || {})) {
            const label = key.replaceAll('_', ' ');
            rows.push({ closed_on: report.closed_on, official_closing_time: report.official_closing_time, activity: 'Closing summary', reference: '', details: label, status: '', amount: /sales|balance|total/.test(key) ? Number(value).toFixed(2) : String(value) });
          }
          const activities = data.activities || {};
          for (const item of activities.appointments || []) rows.push({
            closed_on: report.closed_on, official_closing_time: report.official_closing_time, activity: 'Appointment',
            reference: `APT-${item.id}`, details: `${item.client} · ${item.pet} · ${item.service} · ${item.appointment_at}`,
            status: item.status, amount: ''
          });
          for (const item of activities.invoices || []) rows.push({
            closed_on: report.closed_on, official_closing_time: report.official_closing_time, activity: 'Invoice',
            reference: `INV-${item.id}`, details: `${item.client} · ${item.details} · ${item.payment_method}`,
            status: item.status, amount: Number(item.amount).toFixed(2)
          });
          for (const item of activities.expenses || []) rows.push({
            closed_on: report.closed_on, official_closing_time: report.official_closing_time, activity: 'Expense',
            reference: `EXP-${item.id}`, details: `${item.reason} · ${item.description}`,
            status: item.status, amount: Number(item.amount).toFixed(2)
          });
          for (const item of activities.stock_movements || []) rows.push({
            closed_on: report.closed_on, official_closing_time: report.official_closing_time, activity: item.movement_type,
            reference: `STK-${item.id}`, details: `${item.item} · ${item.reason}`,
            status: '', amount: `${item.quantity} units`
          });
          for (const item of activities.new_clients || []) rows.push({
            closed_on: report.closed_on, official_closing_time: report.official_closing_time, activity: 'New client',
            reference: `CLI-${item.id}`, details: item.full_name, status: '', amount: ''
          });
          for (const item of activities.new_pets || []) rows.push({
            closed_on: report.closed_on, official_closing_time: report.official_closing_time, activity: 'New pet',
            reference: `PET-${item.id}`, details: `${item.name} · ${item.species} · ${item.breed}`, status: '', amount: ''
          });
          for (const item of activities.medical_records || []) rows.push({
            closed_on: report.closed_on, official_closing_time: report.official_closing_time, activity: 'Visit history',
            reference: `VIS-${item.id}`, details: `${item.client} · ${item.pet} · ${item.diagnosis}`, status: '', amount: ''
          });
          return rows.map(row => ({ ...row, details: capitalizeFirst(row.details) }));
        }),
        dateKey: 'closed_on'
      }
    };

    let currentReportRows = [];
    let currentReportDefinition = null;
    let currentDateRange = null;
    let preferredExportFormat = 'csv';
    let reportClinicName = 'Urban Fur Veterinary Clinic';
    const capitalizeFirst = value => {
      const text = String(value ?? '');
      return text ? text[0].toLocaleUpperCase() + text.slice(1) : text;
    };
    const formatPeso = value => `₱${Number(value).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
    const localDate = date => {
      const year = date.getFullYear();
      const month = String(date.getMonth() + 1).padStart(2, '0');
      const day = String(date.getDate()).padStart(2, '0');
      return `${year}-${month}-${day}`;
    };
    const currentDate = localDate(new Date());
    document.getElementById('reportDay').value = currentDate;
    document.getElementById('reportWeek').value = currentDate;
    document.getElementById('reportMonth').value = currentDate.slice(0, 7);

    function getSelectedDateRange() {
      if (!document.getElementById('useReportDateRange').checked) return null;
      const period = document.getElementById('reportPeriod').value;
      if (period === 'daily') {
        const date = document.getElementById('reportDay').value;
        return date ? { start: date, end: date } : null;
      }
      if (period === 'monthly') {
        const month = document.getElementById('reportMonth').value;
        if (!month) return null;
        const [year, monthNumber] = month.split('-').map(Number);
        return { start: `${month}-01`, end: localDate(new Date(year, monthNumber, 0)) };
      }
      if (period === 'weekly') {
        const date = document.getElementById('reportWeek').value;
        if (!date) return null;
        const selected = new Date(`${date}T00:00:00`);
        const weekday = (selected.getDay() + 6) % 7;
        selected.setDate(selected.getDate() - weekday);
        const end = new Date(selected);
        end.setDate(end.getDate() + 6);
        return { start: localDate(selected), end: localDate(end) };
      }
      const start = document.getElementById('reportStartDate').value;
      const end = document.getElementById('reportEndDate').value;
      if (!start || !end || start > end) throw new Error('Choose a valid custom date range.');
      return { start, end };
    }

    function updateReportDateControls() {
      const period = document.getElementById('reportPeriod').value;
      document.getElementById('reportDayControl').hidden = period !== 'daily';
      document.getElementById('reportMonthControl').hidden = period !== 'monthly';
      document.getElementById('reportWeekControl').hidden = period !== 'weekly';
      document.getElementById('reportCustomControl').hidden = period !== 'custom';
      document.getElementById('applyReportDateButton').hidden = period !== 'custom';
    }

    async function initializeReports() {
      try {
        const settings = await clinicRequest('settings');
        reportClinicName = settings.data.clinic_name || reportClinicName;
        preferredExportFormat = settings.data.reports_export_format || 'csv';
        document.getElementById('reportPeriod').value = settings.data.reports_default_period || 'monthly';
        updateReportDateControls();
        setExportButtonLabel();
        await generateReport();
      } catch (error) {
        clinicShowError(error);
      }
    }

    function setExportButtonLabel() {
      const labels = { csv: 'Export CSV', excel: 'Export Excel', pdf: 'Save as PDF' };
      document.getElementById('exportReportButton').innerHTML = `<i class="fa-solid fa-file-export"></i> ${labels[preferredExportFormat] || labels.csv}`;
    }

    function printReport() {
      if (!currentReportDefinition || currentReportRows.length === 0) return;
      document.getElementById('reportPrintHeader').hidden = false;
      window.addEventListener('afterprint', () => { document.getElementById('reportPrintHeader').hidden = true; }, { once: true });
      window.print();
    }

    async function generateReport() {
      const reportType = document.getElementById('reportType').value;
      const definition = reportDefinitions[reportType];
      const closeButton = document.getElementById('closeOperationsButton');
      closeButton.hidden = reportType !== 'end-of-day';
      closeButton.disabled = false;
      const exportButton = document.getElementById('exportReportButton');
      exportButton.disabled = true;
      document.getElementById('printReportButton').disabled = true;
      try {
        currentDateRange = getSelectedDateRange();
        const hasDateRange = Boolean(currentDateRange);
        const [result, invoiceResult] = await Promise.all([
          clinicRequest(definition.resource),
          definition.resource === 'invoices' ? Promise.resolve(null) : clinicRequest('invoices')
        ]);
        currentReportDefinition = definition;
        currentReportRows = definition.map(result.data);
        if (reportType === 'end-of-day') {
          closeButton.disabled = result.data.some(report => String(report.closed_on) === currentDate);
          if (closeButton.disabled) closeButton.innerHTML = '<i class="fa-solid fa-lock"></i> Today’s report is closed';
        } else {
          closeButton.innerHTML = '<i class="fa-solid fa-lock"></i> Close operations &amp; generate report';
        }
        if (hasDateRange && definition.dateKey) {
          currentReportRows = currentReportRows.filter(row => {
            const date = String(row[definition.dateKey] || '').slice(0, 10);
            return date >= currentDateRange.start && date <= currentDateRange.end;
          });
        } else if (hasDateRange && definition.resource === 'invoices') {
          currentReportRows = currentReportRows.filter(row => {
            const date = String(row.date || '').slice(0, 10);
            return date >= currentDateRange.start && date <= currentDateRange.end;
          });
        }
        document.getElementById('reportTableHead').innerHTML = `<tr>${definition.columns.map(([, label]) => `<th>${clinicEscape(label)}</th>`).join('')}</tr>`;
        document.getElementById('reportTableBody').innerHTML = currentReportRows.length
          ? currentReportRows.map(row => `<tr>${definition.columns.map(([key]) => `<td>${clinicEscape(row[key] ?? '')}</td>`).join('')}</tr>`).join('')
          : `<tr><td colspan="${definition.columns.length}">No records found for this report.</td></tr>`;
        document.getElementById('printReportTitle').textContent = document.getElementById('reportType').selectedOptions[0].textContent;
        document.getElementById('printReportRange').textContent = `${hasDateRange ? `Reporting period: ${currentDateRange.start} to ${currentDateRange.end}` : 'All dates'} · ${currentReportRows.length} records`;
        document.getElementById('printGeneratedAt').textContent = `Generated: ${new Date().toLocaleString()}`;
        document.querySelector('#reportPrintHeader h1').textContent = reportClinicName;
        const invoiceRows = (invoiceResult ? invoiceResult.data : result.data).filter(invoice => {
          const date = String(invoice.date || '').slice(0, 10);
          return !hasDateRange || (date >= currentDateRange.start && date <= currentDateRange.end);
        });
        document.getElementById('report-weekly-sales').textContent = formatPeso(invoiceRows
          .filter(invoice => invoice.status === 'Paid')
          .reduce((total, invoice) => total + Number(invoice.amount || 0), 0));
        document.getElementById('report-transactions').textContent = invoiceRows.length.toLocaleString();
        document.getElementById('report-credit').textContent = formatPeso(invoiceRows
          .filter(invoice => invoice.payment_method === 'Pay Later' && ['Pending', 'Overdue'].includes(invoice.status))
          .reduce((total, invoice) => total + Number(invoice.amount || 0), 0));
        document.getElementById('report-monthly-sales').textContent = currentReportRows.length.toLocaleString();
        document.getElementById('exportReportButton').disabled = currentReportRows.length === 0;
        document.getElementById('printReportButton').disabled = currentReportRows.length === 0;
      } catch (error) {
        clinicShowError(error);
      }
    }

    function exportReportCsv() {
      if (!currentReportDefinition || currentReportRows.length === 0) return;
      if (preferredExportFormat === 'pdf') {
        printReport();
        return;
      }
      const quote = value => `"${String(value ?? '').replace(/"/g, '""')}"`;
      const rows = [
        currentReportDefinition.columns.map(([, label]) => quote(label)),
        ...currentReportRows.map(row => currentReportDefinition.columns.map(([key]) => quote(row[key])))
      ];
      const excelRows = [
        currentReportDefinition.columns.map(([, label]) => `<th>${clinicEscape(label)}</th>`),
        ...currentReportRows.map(row => currentReportDefinition.columns.map(([key]) => `<td>${clinicEscape(row[key] ?? '')}</td>`))
      ];
      const isExcel = preferredExportFormat === 'excel';
      const content = isExcel
        ? `<html><head><meta charset="utf-8"></head><body><table>${excelRows.map(row => `<tr>${row.join('')}</tr>`).join('')}</table></body></html>`
        : `\uFEFF${rows.map(row => row.join(',')).join('\r\n')}`;
      const blob = new Blob([content], { type: isExcel ? 'application/vnd.ms-excel;charset=utf-8' : 'text/csv;charset=utf-8' });
      const url = URL.createObjectURL(blob);
      const link = document.createElement('a');
      link.href = url;
      const reportName = document.getElementById('reportType').selectedOptions[0].textContent.toLowerCase().replace(/[^a-z0-9]+/g, '-');
      const extension = isExcel ? 'xls' : 'csv';
      const dateSuffix = currentDateRange ? `${currentDateRange.start}-to-${currentDateRange.end}` : 'all-dates';
      link.download = `${reportName}-${dateSuffix}.${extension}`;
      link.click();
      window.setTimeout(() => URL.revokeObjectURL(url), 1000);
    }

    document.getElementById('exportReportButton').addEventListener('click', exportReportCsv);
    document.getElementById('printReportButton').addEventListener('click', printReport);
    document.getElementById('reportPeriod').addEventListener('change', () => {
      updateReportDateControls();
      if (document.getElementById('reportPeriod').value !== 'custom') generateReport();
    });
    document.getElementById('useReportDateRange').addEventListener('change', event => {
      document.getElementById('reportDateControls').hidden = !event.target.checked;
      if (event.target.checked) generateReport();
      else generateReport();
    });
    document.getElementById('reportType').addEventListener('change', generateReport);
    document.getElementById('closeOperationsButton').addEventListener('click', async event => {
      const button = event.currentTarget;
      if (!confirm('Close today’s clinic operations and securely save the consolidated End-of-Day Report?')) return;
      button.disabled = true;
      try {
        const result = await clinicRequest('end-of-day-reports', 'close', {});
        document.getElementById('reportType').value = 'end-of-day';
        currentReportRows = [];
        const definition = reportDefinitions['end-of-day'];
        currentReportDefinition = definition;
        currentReportRows = definition.map([result.data]);
        currentDateRange = { start: result.data.closed_on, end: result.data.closed_on };
        document.getElementById('reportPeriod').value = 'daily';
        document.getElementById('reportDay').value = result.data.closed_on;
        updateReportDateControls();
        document.getElementById('reportTableHead').innerHTML = `<tr>${definition.columns.map(([, label]) => `<th>${clinicEscape(label)}</th>`).join('')}</tr>`;
        document.getElementById('reportTableBody').innerHTML = currentReportRows.length
          ? currentReportRows.map(row => `<tr>${definition.columns.map(([key]) => `<td>${clinicEscape(row[key] ?? '')}</td>`).join('')}</tr>`).join('')
          : `<tr><td colspan="${definition.columns.length}">No records found for this report.</td></tr>`;
        document.getElementById('printReportTitle').textContent = 'End-of-Day Report';
        document.getElementById('printReportRange').textContent = `Clinic operations closed: ${result.data.closed_on} · Official closing time: ${result.data.official_closing_time}`;
        document.getElementById('printGeneratedAt').textContent = `Generated: ${new Date().toLocaleString()}`;
        document.getElementById('report-weekly-sales').textContent = formatPeso(result.data.report.totals.paid_sales);
        document.getElementById('report-transactions').textContent = String(result.data.report.totals.invoices);
        document.getElementById('report-credit').textContent = formatPeso(result.data.report.totals.unpaid_invoice_balance);
        document.getElementById('report-monthly-sales').textContent = String(currentReportRows.length);
        document.getElementById('exportReportButton').disabled = currentReportRows.length === 0;
        document.getElementById('printReportButton').disabled = currentReportRows.length === 0;
        button.innerHTML = '<i class="fa-solid fa-lock"></i> Today’s report is closed';
      } catch (error) {
        clinicShowError(error);
        button.disabled = false;
      }
    });
    document.getElementById('reportDay').addEventListener('change', generateReport);
    document.getElementById('reportWeek').addEventListener('change', generateReport);
    document.getElementById('reportMonth').addEventListener('change', generateReport);
    document.getElementById('applyReportDateButton').addEventListener('click', generateReport);
    document.getElementById('reportStartDate').addEventListener('change', () => {
      document.getElementById('reportEndDate').min = document.getElementById('reportStartDate').value;
    });
    updateReportDateControls();
    initializeReports();
  </script>
</body>
</html>
