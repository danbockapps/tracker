const ChangeEmail = () => {
  const [oldEmail, setOldEmail] = React.useState('')
  const [newEmail, setNewEmail] = React.useState('')
  const [loading, setLoading] = React.useState('')

  const onSubmit = e => {
    setLoading(true)
    setOldEmail('')
    setNewEmail('')

    fetch('rest/api.php?q=changeemail', { method: 'POST', body: { oldEmail, newEmail } }).then(
      response => {
        setLoading(false)
        console.log('response', response)
      },
    )
    e.preventDefault()
  }

  return (
    <form {...{ onSubmit }}>
      <TextField
        label='Old email address'
        value={oldEmail}
        changeHandler={e => setOldEmail(e.target.value)}
      />
      <TextField
        label='New email address'
        value={newEmail}
        changeHandler={e => setNewEmail(e.target.value)}
      />

      <div id='spacious-form-button-spinner'>
        {loading ? <img src='spinner.gif' /> : <button type='submit'>Save</button>}
      </div>
    </form>
  )
}

ReactDOM.render(React.createElement(ChangeEmail), document.querySelector('#change-email'))
