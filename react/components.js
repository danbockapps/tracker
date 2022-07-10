const TextField = ({ label, name, value, changeHandler }) => (
  <label>
    {label}
    <input name={name} type='text' value={value} onChange={changeHandler} />
  </label>
)
